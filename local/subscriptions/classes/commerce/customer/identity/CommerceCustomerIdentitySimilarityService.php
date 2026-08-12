<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\identity;

defined('MOODLE_INTERNAL') || die();

use core_text;
use moodle_database;

/**
 * Read-only similarity engine used to find identities which may belong to one person.
 *
 * M7.1/M7.2 deliberately separates candidate discovery from scoring. Broad, cheap
 * buckets improve recall; weighted and explainable signals then keep the result safe
 * for an administrator to review. This service never mutates an account.
 */
final class CommerceCustomerIdentitySimilarityService {
    public const DEFAULT_MIN_SCORE = 50;
    public const MAX_USERS_SCANNED = 1500;
    private const MAX_BUCKET_SIZE = 80;

    public const REASON_EMAIL_EXACT = 'email_exact';
    public const REASON_EMAIL_LOCAL_EXACT = 'email_local_exact';
    public const REASON_EMAIL_LOCAL_CLOSE = 'email_local_close';
    public const REASON_EMAIL_DOMAIN_CLOSE = 'email_domain_close';
    public const REASON_NAME_EXACT = 'name_exact';
    public const REASON_NAME_REVERSED = 'name_reversed';
    public const REASON_FIRSTNAME_CLOSE = 'firstname_close';
    public const REASON_LASTNAME_CLOSE = 'lastname_close';
    public const REASON_ALTERNATE_NAME = 'alternate_name';
    public const REASON_PHONE_EXACT = 'phone_exact';
    public const REASON_EMAIL_NAME_COMBINATION = 'email_name_combination';

    public function __construct(private readonly moodle_database $database) {
    }

    /** @return array{matches:CommerceCustomerIdentitySimilarityMatch[],scanned:int,truncated:bool} */
    public function search(array $criteria = []): array {
        $q = trim((string)($criteria['q'] ?? ''));
        $status = trim((string)($criteria['status'] ?? ''));
        $minscore = max(0, min(100, (int)($criteria['minscore'] ?? self::DEFAULT_MIN_SCORE)));
        [$sql, $params] = $this->user_query($q, $status);
        $records = array_values($this->database->get_records_sql($sql, $params, 0, self::MAX_USERS_SCANNED + 1));
        $truncated = count($records) > self::MAX_USERS_SCANNED;
        if ($truncated) {
            $records = array_slice($records, 0, self::MAX_USERS_SCANNED);
        }
        $profiles = [];
        foreach ($records as $record) {
            $profiles[(int)$record->id] = $this->profile($record);
        }
        $matches = [];
        foreach ($this->candidate_pairs($profiles) as [$firstid, $secondid]) {
            $match = $this->compare($profiles[$firstid]['user'], $profiles[$secondid]['user']);
            if ($match !== null && $match->score >= $minscore) {
                $matches[$match->key()] = $match;
            }
        }
        $matches = array_values($matches);
        usort($matches, static fn($a, $b): int => $b->score <=> $a->score ?: strcmp($a->key(), $b->key()));
        return ['matches' => $matches, 'scanned' => count($records), 'truncated' => $truncated];
    }

    /** @return CommerceCustomerIdentitySimilarityMatch[] */
    public function suggest_for_external_identity(string $email, string $firstname, string $lastname, int $minscore = 60, int $limit = 5): array {
        global $CFG;
        $email = core_text::strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname = trim($lastname);
        $guest = (object)['id' => 0, 'email' => $email, 'firstname' => $firstname, 'lastname' => $lastname,
            'firstnamephonetic' => '', 'lastnamephonetic' => '', 'middlename' => '', 'alternatename' => '', 'phone1' => '', 'phone2' => ''];
        $where = ['deleted = 0', 'id > 1', 'mnethostid = :mnethostid'];
        $params = ['mnethostid' => (int)$CFG->mnet_localhost_id];
        $signals = [];
        if ($firstname !== '') {
            $signals[] = $this->database->sql_equal('firstname', ':firstname', false);
            $params['firstname'] = $firstname;
        }
        if ($lastname !== '') {
            $signals[] = $this->database->sql_equal('lastname', ':lastname', false);
            $params['lastname'] = $lastname;
        }
        $local = explode('@', $email, 2)[0] ?? '';
        if (strlen($local) >= 4) {
            $signals[] = $this->database->sql_like('email', ':emailprefix', false, false);
            $params['emailprefix'] = $this->database->sql_like_escape(substr($local, 0, 4)) . '%@%';
        }
        if ($signals === []) {
            return [];
        }
        $where[] = '(' . implode(' OR ', $signals) . ')';
        $users = $this->database->get_records_sql('SELECT id, username, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, email, phone1, phone2, confirmed, suspended, timecreated, lastaccess FROM {user} WHERE ' . implode(' AND ', $where) . ' ORDER BY lastaccess DESC, id DESC', $params, 0, 150);
        $matches = [];
        foreach ($users as $user) {
            $match = $this->compare($guest, $user);
            if ($match !== null && $match->score >= max(0, min(100, $minscore))) {
                $matches[] = $match;
            }
        }
        usort($matches, static fn($a, $b): int => $b->score <=> $a->score);
        return array_slice($matches, 0, max(1, min(20, $limit)));
    }

    public function compare(\stdClass $first, \stdClass $second): ?CommerceCustomerIdentitySimilarityMatch {
        if ((int)$first->id === (int)$second->id) {
            return null;
        }
        $a = $this->profile($first);
        $b = $this->profile($second);
        $signals = [];
        if ($a['email'] !== '' && $a['email'] === $b['email']) {
            $signals[self::REASON_EMAIL_EXACT] = 100;
        } else {
            if ($a['emaillocal'] !== '' && $a['emaillocal'] === $b['emaillocal']) {
                $signals[self::REASON_EMAIL_LOCAL_EXACT] = 38;
            } elseif (min(strlen($a['emaillocal']), strlen($b['emaillocal'])) >= 4 && $this->ratio($a['emaillocal'], $b['emaillocal']) >= .72) {
                $signals[self::REASON_EMAIL_LOCAL_CLOSE] = 28;
            }
            if ($a['emaildomain'] !== '' && $a['emaildomain'] !== $b['emaildomain'] && $this->domain_is_close($a['emaildomain'], $b['emaildomain'])) {
                $signals[self::REASON_EMAIL_DOMAIN_CLOSE] = 12;
            }
            if ($a['fullname'] !== '' && $a['fullname'] === $b['fullname']) {
                $signals[self::REASON_NAME_EXACT] = 55;
            } elseif ($a['firstname'] !== '' && $a['lastname'] !== '' && $a['firstname'] === $b['lastname'] && $a['lastname'] === $b['firstname']) {
                $signals[self::REASON_NAME_REVERSED] = 52;
            } else {
                if (min(strlen($a['firstname']), strlen($b['firstname'])) >= 3 && $this->ratio($a['firstname'], $b['firstname']) >= .78) {
                    $signals[self::REASON_FIRSTNAME_CLOSE] = 18;
                }
                if (min(strlen($a['lastname']), strlen($b['lastname'])) >= 3 && $this->ratio($a['lastname'], $b['lastname']) >= .78) {
                    $signals[self::REASON_LASTNAME_CLOSE] = 24;
                }
            }
            if ($this->alternate_names_intersect($a, $b)) {
                $signals[self::REASON_ALTERNATE_NAME] = 18;
            }
            if (isset($signals[self::REASON_EMAIL_LOCAL_CLOSE]) && ($a['lastname'] !== '' && $a['lastname'] === $b['lastname'])) {
                $signals[self::REASON_EMAIL_NAME_COMBINATION] = 12;
            }
            if ($a['phones'] !== [] && array_intersect($a['phones'], $b['phones']) !== []) {
                $signals[self::REASON_PHONE_EXACT] = 75;
            }
        }
        if ($signals === []) {
            return null;
        }
        $score = min(100, array_sum($signals));
        return new CommerceCustomerIdentitySimilarityMatch($first, $second, $score, array_keys($signals), $signals);
    }

    private function candidate_pairs(array $profiles): array {
        $buckets = [];
        foreach ($profiles as $userid => $p) {
            $keys = [];
            if (strlen($p['emaillocal']) >= 4) {
                $keys[] = 'email4:' . substr($p['emaillocal'], 0, 4);
            }
            if ($p['fullname'] !== '') {
                $keys[] = 'fullname:' . $p['fullname'];
            }
            foreach ([$p['firstname'], $p['lastname']] as $name) {
                if (strlen($name) >= 3) {
                    $keys[] = 'name3:' . substr($name, 0, 3);
                }
            }
            foreach ($p['names'] as $name) {
                if (strlen($name) >= 4) {
                    $keys[] = 'alt4:' . substr($name, 0, 4);
                }
            }
            foreach ($p['phones'] as $phone) {
                $keys[] = 'phone:' . $phone;
            }
            foreach (array_unique($keys) as $key) {
                $buckets[$key][] = $userid;
            }
        }
        $pairs = [];
        foreach ($buckets as $ids) {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (count($ids) < 2 || count($ids) > self::MAX_BUCKET_SIZE) {
                continue;
            }
            sort($ids, SORT_NUMERIC);
            for ($i = 0, $count = count($ids); $i < $count - 1; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $pairs[$ids[$i] . ':' . $ids[$j]] = [$ids[$i], $ids[$j]];
                }
            }
        }
        return array_values($pairs);
    }

    private function profile(\stdClass $user): array {
        $email = core_text::strtolower(trim((string)($user->email ?? '')));
        $emailparts = explode('@', $email, 2);
        $firstname = $this->normalise((string)($user->firstname ?? ''));
        $lastname = $this->normalise((string)($user->lastname ?? ''));
        $names = array_values(array_unique(array_filter([
            $firstname, $lastname,
            $this->normalise((string)($user->firstnamephonetic ?? '')),
            $this->normalise((string)($user->lastnamephonetic ?? '')),
            $this->normalise((string)($user->middlename ?? '')),
            $this->normalise((string)($user->alternatename ?? '')),
        ])));
        $phones = [];
        foreach (['phone1', 'phone2'] as $field) {
            $phone = preg_replace('/[^0-9]+/', '', (string)($user->{$field} ?? ''));
            if (strlen($phone) >= 7) {
                $phones[] = substr($phone, -9);
            }
        }
        return ['user' => $user, 'email' => $email, 'emaillocal' => $this->normalise($emailparts[0] ?? ''),
            'emaildomain' => $this->normalise_domain($emailparts[1] ?? ''), 'firstname' => $firstname, 'lastname' => $lastname,
            'fullname' => trim($firstname . ' ' . $lastname), 'names' => $names, 'phones' => array_values(array_unique($phones))];
    }

    private function alternate_names_intersect(array $a, array $b): bool {
        $primarya = array_filter([$a['firstname'], $a['lastname']]);
        $primaryb = array_filter([$b['firstname'], $b['lastname']]);
        $extraa = array_diff($a['names'], $primarya);
        $extrab = array_diff($b['names'], $primaryb);
        return array_intersect($extraa, $b['names']) !== [] || array_intersect($extrab, $a['names']) !== [];
    }

    private function domain_is_close(string $a, string $b): bool {
        if ($a === '' || $b === '' || $a === $b) {
            return false;
        }
        // A one-character typo catches gmal/gmail, hotmial/hotmail, etc. without
        // treating unrelated providers as equivalent.
        return levenshtein($a, $b) <= 1 || $this->ratio($a, $b) >= .88;
    }

    private function normalise_domain(string $value): string {
        return preg_replace('/[^a-z0-9.-]+/', '', core_text::strtolower(trim($value))) ?? '';
    }

    private function normalise(string $value): string {
        $value = core_text::strtolower(trim($value));
        $ascii = preg_replace('/[^a-z0-9]+/', '', core_text::specialtoascii($value)) ?? '';
        if ($ascii !== '') {
            return $ascii;
        }
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    private function ratio(string $first, string $second): float {
        if ($first === '' || $second === '') return 0.0;
        if ($first === $second) return 1.0;
        $max = max(strlen($first), strlen($second));
        return $max === 0 ? 1.0 : max(0.0, 1.0 - levenshtein($first, $second) / $max);
    }

    private function user_query(string $q, string $status): array {
        $where = ['u.deleted = 0', 'u.id > 1'];
        $params = [];
        if ($status === 'active') $where[] = 'u.suspended = 0';
        elseif ($status === 'suspended') $where[] = 'u.suspended = 1';
        if ($q !== '') {
            $like = '%' . $this->database->sql_like_escape($q) . '%';
            $fields = ['email', 'firstname', 'lastname', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename'];
            $clauses = [];
            foreach ($fields as $field) {
                $key = 'q' . $field;
                $clauses[] = $this->database->sql_like('u.' . $field, ':' . $key, false, false);
                $params[$key] = $like;
            }
            $where[] = '(' . implode(' OR ', $clauses) . ')';
        }
        return ['SELECT u.id, u.username, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email, u.phone1, u.phone2, u.confirmed, u.suspended, u.timecreated, u.lastaccess FROM {user} u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.lastaccess DESC, u.id DESC', $params];
    }
}
