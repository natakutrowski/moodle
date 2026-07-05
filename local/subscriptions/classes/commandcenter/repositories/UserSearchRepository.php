<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

final class UserSearchRepository {

    public function search(string $text, int $limit = 10): array {
        global $DB;

        $text = trim($text);

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        $like = '%' . $DB->sql_like_escape($text) . '%';

        $searchfield = $DB->sql_concat(
            'u.firstname', "' '",
            'u.lastname', "' '",
            'u.email', "' '",
            'u.username'
        );

        $likesql = $DB->sql_like($searchfield, ':query', false, false);

        $sql = "
            SELECT u.id, u.firstname, u.lastname, u.email, u.username, u.suspended
              FROM {user} u
             WHERE u.deleted = 0
               AND $likesql
          ORDER BY u.suspended ASC, u.lastname ASC, u.firstname ASC, u.email ASC
        ";

        return array_values($DB->get_records_sql($sql, ['query' => $like], 0, $limit));
    }

    public function find_by_id(int $id): ?object {
        global $DB;

        $user = $DB->get_record('user', [
            'id' => $id,
            'deleted' => 0,
        ], 'id, firstname, lastname, email, username, suspended');

        return $user ?: null;
    }
}