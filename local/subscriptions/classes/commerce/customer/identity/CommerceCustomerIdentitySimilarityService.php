<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use core_text;
use moodle_database;

/**
 * Finds potentially duplicated Moodle customer accounts.
 *
 * The service only proposes candidates. It never reconciles, merges, suspends
 * or otherwise modifies an account.
 */
final class CommerceCustomerIdentitySimilarityService {
    public const DEFAULT_MIN_SCORE = 60;
    public const MAX_USERS_SCANNED = 1500;
    private const MAX_BUCKET_SIZE = 60;

    public const REASON_EMAIL_EXACT = 'email_exact';
    public const REASON_EMAIL_LOCAL_EXACT = 'email_local_exact';
    public const REASON_EMAIL_LOCAL_CLOSE = 'email_local_close';
    public const REASON_NAME_EXACT = 'name_exact';
    public const REASON_NAME_REVERSED = 'name_reversed';
    public const REASON_FIRSTNAME_CLOSE = 'firstname_close';
    public const REASON_LASTNAME_CLOSE = 'lastname_close';
    public const REASON_PHONE_EXACT = 'phone_exact';

    public function __construct(
        private readonly moodle_database $database
    ) {
    }

    /**
     * @param array{q?:string,status?:string,minscore?:int} $criteria
     * @return array{
     *     matches:CommerceCustomerIdentitySimilarityMatch[],
     *     scanned:int,
     *     truncated:bool
     * }
     */
    public function search(array $criteria = []): array {
        $q = trim((string)($criteria['q'] ?? ''));
        $status = trim((string)($criteria['status'] ?? ''));
        $minscore = max(
            0,
            min(100, (int)($criteria['minscore'] ?? self::DEFAULT_MIN_SCORE))
        );

        [$sql, $params] = $this->user_query($q, $status);
        $records = array_values($this->database->get_records_sql(
            $sql,
            $params,
            0,
            self::MAX_USERS_SCANNED + 1
        ));

        $truncated = count($records) > self::MAX_USERS_SCANNED;
        if ($truncated) {
            $records = array_slice($records, 0, self::MAX_USERS_SCANNED);
        }

        $profiles = [];
        foreach ($records as $record) {
            $profiles[(int)$record->id] = $this->profile($record);
        }

        $pairkeys = $this->candidate_pairs($profiles);
        $matches = [];

        foreach ($pairkeys as [$firstid, $secondid]) {
            $match = $this->compare(
                $profiles[$firstid]['user'],
                $profiles[$secondid]['user']
            );
            if ($match !== null && $match->score >= $minscore) {
                $matches[$match->key()] = $match;
            }
        }

        $matches = array_values($matches);
        usort(
            $matches,
            static function(
                CommerceCustomerIdentitySimilarityMatch $a,
                CommerceCustomerIdentitySimilarityMatch $b
            ): int {
                if ($a->score !== $b->score) {
                    return $b->score <=> $a->score;
                }
                return $a->key() <=> $b->key();
            }
        );

        return [
            'matches' => $matches,
            'scanned' => count($records),
            'truncated' => $truncated,
        ];
    }

    /**
     * Find existing Moodle accounts that look similar to an external identity.
     *
     * This is intentionally conservative and read-only. It is used to stop
     * automatic account creation when an administrator should review a likely
     * pre-existing account first.
     *
     * @return CommerceCustomerIdentitySimilarityMatch[]
     */
    public function suggest_for_external_identity(
        string $email,
        string $firstname,
        string $lastname,
        int $minscore = 70,
        int $limit = 5
    ): array {
        global $CFG;

        $email = \core_text::strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname = trim($lastname);
        $minscore = max(0, min(100, $minscore));
        $limit = max(1, min(20, $limit));

        $guest = (object)[
            'id' => 0,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'firstnamephonetic' => '',
            'lastnamephonetic' => '',
            'middlename' => '',
            'alternatename' => '',
            'phone1' => '',
            'phone2' => '',
        ];

        $where = [
            'deleted = 0',
            'id > 1',
            'mnethostid = :mnethostid',
        ];
        $params = [
            'mnethostid' => (int)$CFG->mnet_localhost_id,
        ];
        $signals = [];

        if ($firstname !== '' && $lastname !== '') {
            $signals[] = '('
                . $this->database->sql_equal('firstname', ':firstname', false)
                . ' AND '
                . $this->database->sql_equal('lastname', ':lastname', false)
                . ')';
            $params['firstname'] = $firstname;
            $params['lastname'] = $lastname;

            $signals[] = '('
                . $this->database->sql_equal('firstname', ':reversedfirstname', false)
                . ' AND '
                . $this->database->sql_equal('lastname', ':reversedlastname', false)
                . ')';
            $params['reversedfirstname'] = $lastname;
            $params['reversedlastname'] = $firstname;
        }

        $localpart = explode('@', $email, 2)[0] ?? '';
        if (strlen($localpart) >= 5) {
            $prefix = substr($localpart, 0, 5);
            $signals[] = $this->database->sql_like(
                'email',
                ':emailprefix',
                false,
                false
            );
            $params['emailprefix'] =
                $this->database->sql_like_escape($prefix) . '%@%';
        }

        if ($signals === []) {
            return [];
        }

        $where[] = '(' . implode(' OR ', $signals) . ')';

        $users = $this->database->get_records_sql(
            'SELECT id, username, firstname, lastname,'
                . ' firstnamephonetic, lastnamephonetic,'
                . ' middlename, alternatename, email, phone1, phone2,'
                . ' confirmed, suspended, timecreated, lastaccess'
                . ' FROM {user}'
                . ' WHERE ' . implode(' AND ', $where)
                . ' ORDER BY lastaccess DESC, id DESC',
            $params,
            0,
            100
        );

        $matches = [];
        foreach ($users as $user) {
            $match = $this->compare($guest, $user);
            if ($match !== null && $match->score >= $minscore) {
                $matches[] = $match;
            }
        }

        usort(
            $matches,
            static fn(
                CommerceCustomerIdentitySimilarityMatch $a,
                CommerceCustomerIdentitySimilarityMatch $b
            ): int => $b->score <=> $a->score
        );

        return array_slice($matches, 0, $limit);
    }

    public function compare(
        \stdClass $first,
        \stdClass $second
    ): ?CommerceCustomerIdentitySimilarityMatch {
        if ((int)$first->id === (int)$second->id) {
            return null;
        }

        $a = $this->profile($first);
        $b = $this->profile($second);
        $score = 0;
        $reasons = [];

        if (
            $a['email'] !== ''
            && $a['email'] === $b['email']
        ) {
            $score = 100;
            $reasons[] = self::REASON_EMAIL_EXACT;
        } else {
            if (
                $a['emaillocal'] !== ''
                && $a['emaillocal'] === $b['emaillocal']
            ) {
                $score += 35;
                $reasons[] = self::REASON_EMAIL_LOCAL_EXACT;
            } elseif (
                strlen($a['emaillocal']) >= 5
                && strlen($b['emaillocal']) >= 5
                && $this->ratio($a['emaillocal'], $b['emaillocal']) >= 0.84
            ) {
                $score += 25;
                $reasons[] = self::REASON_EMAIL_LOCAL_CLOSE;
            }

            if (
                $a['fullname'] !== ''
                && $a['fullname'] === $b['fullname']
            ) {
                $score += 65;
                $reasons[] = self::REASON_NAME_EXACT;
            } elseif (
                $a['firstname'] !== ''
                && $a['lastname'] !== ''
                && $a['firstname'] === $b['lastname']
                && $a['lastname'] === $b['firstname']
            ) {
                $score += 60;
                $reasons[] = self::REASON_NAME_REVERSED;
            } else {
                $firstnamesimilarity = $this->ratio(
                    $a['firstname'],
                    $b['firstname']
                );
                $lastnamesimilarity = $this->ratio(
                    $a['lastname'],
                    $b['lastname']
                );

                if (
                    min(strlen($a['firstname']), strlen($b['firstname'])) >= 3
                    && $firstnamesimilarity >= 0.82
                ) {
                    $score += 20;
                    $reasons[] = self::REASON_FIRSTNAME_CLOSE;
                }
                if (
                    min(strlen($a['lastname']), strlen($b['lastname'])) >= 3
                    && $lastnamesimilarity >= 0.82
                ) {
                    $score += 25;
                    $reasons[] = self::REASON_LASTNAME_CLOSE;
                }
            }

            if (
                $a['phones'] !== []
                && array_intersect($a['phones'], $b['phones']) !== []
            ) {
                $score += 75;
                $reasons[] = self::REASON_PHONE_EXACT;
            }
        }

        $score = min(100, $score);
        if ($score <= 0 || $reasons === []) {
            return null;
        }

        return new CommerceCustomerIdentitySimilarityMatch(
            $first,
            $second,
            $score,
            array_values(array_unique($reasons))
        );
    }

    /**
     * @param array<int,array<string,mixed>> $profiles
     * @return array<int,array{0:int,1:int}>
     */
    private function candidate_pairs(array $profiles): array {
        $buckets = [];

        foreach ($profiles as $userid => $profile) {
            $keys = [];

            if (strlen($profile['emaillocal']) >= 5) {
                $keys[] = 'email:' . substr($profile['emaillocal'], 0, 5);
            }
            if ($profile['fullname'] !== '') {
                $keys[] = 'fullname:' . $profile['fullname'];
            }
            if (
                $profile['firstname'] !== ''
                && $profile['lastname'] !== ''
            ) {
                $parts = [$profile['firstname'], $profile['lastname']];
                sort($parts, SORT_STRING);
                $keys[] = 'nameparts:' . implode(':', $parts);
                $keys[] = 'lastname:' . $profile['lastname']
                    . ':' . substr($profile['firstname'], 0, 1);
            }
            foreach ($profile['phones'] as $phone) {
                $keys[] = 'phone:' . $phone;
            }

            foreach (array_unique($keys) as $key) {
                $buckets[$key][] = $userid;
            }
        }

        $pairs = [];
        foreach ($buckets as $ids) {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (
                count($ids) < 2
                || count($ids) > self::MAX_BUCKET_SIZE
            ) {
                continue;
            }

            sort($ids, SORT_NUMERIC);
            $count = count($ids);
            for ($i = 0; $i < $count - 1; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $key = $ids[$i] . ':' . $ids[$j];
                    $pairs[$key] = [$ids[$i], $ids[$j]];
                }
            }
        }

        return array_values($pairs);
    }

    /**
     * @return array<string,mixed>
     */
    private function profile(\stdClass $user): array {
        $email = core_text::strtolower(trim((string)($user->email ?? '')));
        $parts = explode('@', $email, 2);

        $firstname = $this->normalise((string)($user->firstname ?? ''));
        $lastname = $this->normalise((string)($user->lastname ?? ''));

        $phones = [];
        foreach (['phone1', 'phone2'] as $field) {
            $phone = preg_replace(
                '/[^0-9]+/',
                '',
                (string)($user->{$field} ?? '')
            );
            if (strlen($phone) >= 7) {
                // Compare the stable national subscriber tail so equivalent
                // formats such as +33 6 12... and 06 12... converge.
                $phones[] = substr($phone, -9);
            }
        }

        return [
            'user' => $user,
            'email' => $email,
            'emaillocal' => $this->normalise($parts[0] ?? ''),
            'firstname' => $firstname,
            'lastname' => $lastname,
            'fullname' => trim($firstname . ' ' . $lastname),
            'phones' => array_values(array_unique($phones)),
        ];
    }

    private function normalise(string $value): string {
        $value = core_text::strtolower(trim($value));
        $value = core_text::specialtoascii($value);
        $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
        return trim($value);
    }

    private function ratio(string $first, string $second): float {
        if ($first === '' || $second === '') {
            return 0.0;
        }
        if ($first === $second) {
            return 1.0;
        }

        $max = max(strlen($first), strlen($second));
        if ($max === 0) {
            return 1.0;
        }

        return max(
            0.0,
            1.0 - (levenshtein($first, $second) / $max)
        );
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function user_query(string $q, string $status): array {
        $where = [
            'u.deleted = 0',
            'u.id > 1',
        ];
        $params = [];

        if ($status === 'active') {
            $where[] = 'u.suspended = 0';
        } elseif ($status === 'suspended') {
            $where[] = 'u.suspended = 1';
        }

        if ($q !== '') {
            $like = '%' . $this->database->sql_like_escape($q) . '%';
            $where[] = '('
                . $this->database->sql_like('u.email', ':qemail', false, false)
                . ' OR '
                . $this->database->sql_like('u.firstname', ':qfirstname', false, false)
                . ' OR '
                . $this->database->sql_like('u.lastname', ':qlastname', false, false)
                . ')';
            $params = [
                'qemail' => $like,
                'qfirstname' => $like,
                'qlastname' => $like,
            ];
        }

        return [
            'SELECT u.id, u.username, u.firstname, u.lastname,'
                . ' u.firstnamephonetic, u.lastnamephonetic,'
                . ' u.middlename, u.alternatename, u.email,'
                . ' u.phone1, u.phone2, u.confirmed, u.suspended,'
                . ' u.timecreated, u.lastaccess'
                . ' FROM {user} u'
                . ' WHERE ' . implode(' AND ', $where)
                . ' ORDER BY u.lastaccess DESC, u.id DESC',
            $params,
        ];
    }
}
