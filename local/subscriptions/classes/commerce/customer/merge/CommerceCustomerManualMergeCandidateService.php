<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Finds Moodle accounts for an explicit, administrator-driven merge.
 *
 * This service is deliberately independent from the similarity engine: an
 * administrator must be able to select two accounts even when no automatic
 * association was proposed.
 */
final class CommerceCustomerManualMergeCandidateService {
    public const DEFAULT_LIMIT = 12;
    public const MAX_LIMIT = 30;

    public function __construct(private readonly moodle_database $database) {
    }

    /**
     * @param int[] $excludeuserids
     * @return \stdClass[]
     */
    public function search(
        string $query,
        array $excludeuserids = [],
        int $limit = self::DEFAULT_LIMIT
    ): array {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $limit = max(1, min(self::MAX_LIMIT, $limit));
        $excludeuserids = array_values(array_unique(array_filter(
            array_map('intval', $excludeuserids),
            static fn(int $userid): bool => $userid > 0
        )));

        $params = [];
        $conditions = ['u.deleted = 0', 'u.id > 1'];

        if ($excludeuserids !== []) {
            [$notinsql, $notinparams] = $this->database->get_in_or_equal(
                $excludeuserids,
                SQL_PARAMS_NAMED,
                'exclude',
                false
            );
            $conditions[] = 'u.id ' . $notinsql;
            $params += $notinparams;
        }

        $needle = '%' . $this->database->sql_like_escape($query) . '%';
        $matches = [
            $this->database->sql_like('u.email', ':email', false),
            $this->database->sql_like('u.username', ':username', false),
            $this->database->sql_like('u.firstname', ':firstname', false),
            $this->database->sql_like('u.lastname', ':lastname', false),
            $this->database->sql_like(
                $this->database->sql_concat('u.firstname', "' '", 'u.lastname'),
                ':fullname',
                false
            ),
        ];
        $params += [
            'email' => $needle,
            'username' => $needle,
            'firstname' => $needle,
            'lastname' => $needle,
            'fullname' => $needle,
        ];

        if (ctype_digit($query) && (int)$query > 1) {
            $matches[] = 'u.id = :exactid';
            $params['exactid'] = (int)$query;
        }

        $conditions[] = '(' . implode(' OR ', $matches) . ')';

        $sql = 'SELECT u.id,u.username,u.firstname,u.lastname,'
            . 'u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,'
            . 'u.email,u.confirmed,u.suspended,u.lastaccess,u.timecreated '
            . 'FROM {user} u '
            . 'WHERE ' . implode(' AND ', $conditions) . ' '
            . 'ORDER BY u.id DESC';

        $records = array_values($this->database->get_records_sql(
            $sql,
            $params,
            0,
            $limit
        ));

        usort($records, function(\stdClass $a, \stdClass $b) use ($query): int {
            $ranka = $this->rank($a, $query);
            $rankb = $this->rank($b, $query);
            return $rankb <=> $ranka ?: ((int)$b->id <=> (int)$a->id);
        });

        return $records;
    }

    /** @param int[] $userids @return \stdClass[] */
    public function selected(array $userids): array {
        $userids = array_values(array_unique(array_filter(
            array_map('intval', $userids),
            static fn(int $userid): bool => $userid > 1
        )));
        if ($userids === []) {
            return [];
        }

        [$insql, $params] = $this->database->get_in_or_equal(
            $userids,
            SQL_PARAMS_NAMED,
            'selected'
        );
        $records = $this->database->get_records_sql(
            'SELECT u.id,u.username,u.firstname,u.lastname,'
                . 'u.firstnamephonetic,u.lastnamephonetic,u.middlename,u.alternatename,'
                . 'u.email,u.confirmed,u.suspended,u.lastaccess,u.timecreated '
                . 'FROM {user} u WHERE u.deleted = 0 AND u.id ' . $insql,
            $params
        );

        $ordered = [];
        foreach ($userids as $userid) {
            if (isset($records[$userid])) {
                $ordered[] = $records[$userid];
            }
        }
        return $ordered;
    }

    private function rank(\stdClass $user, string $query): int {
        $query = mb_strtolower(trim($query));
        $email = mb_strtolower((string)$user->email);
        $username = mb_strtolower((string)$user->username);
        $fullname = mb_strtolower(trim((string)$user->firstname . ' ' . (string)$user->lastname));

        if (ctype_digit($query) && (int)$query === (int)$user->id) {
            return 1000;
        }
        if ($email === $query) {
            return 900;
        }
        if ($username === $query) {
            return 850;
        }
        if ($fullname === $query) {
            return 800;
        }
        if (str_starts_with($email, $query)) {
            return 700;
        }
        if (str_starts_with($fullname, $query)) {
            return 650;
        }
        return 100;
    }
}
