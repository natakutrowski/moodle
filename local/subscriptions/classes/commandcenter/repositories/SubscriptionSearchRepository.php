<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

final class SubscriptionSearchRepository {

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
            'p.name', "' '",
            's.transactionid', "' '",
            's.status'
        );

        $likesql = $DB->sql_like($searchfield, ':query', false, false);

        $sql = $this->base_sql() . "
             WHERE u.deleted = 0
               AND $likesql
          ORDER BY s.creation_date DESC
        ";

        return array_values($DB->get_records_sql($sql, ['query' => $like], 0, $limit));
    }

    public function find_by_id(int $id): ?object {
        global $DB;

        $sql = $this->base_sql() . "
             WHERE s.id = :id
               AND u.deleted = 0
        ";

        $subscription = $DB->get_record_sql($sql, ['id' => $id]);

        return $subscription ?: null;
    }

    private function base_sql(): string {
        return "
            SELECT s.id,
                   s.userid,
                   s.status,
                   s.start_date,
                   s.end_date,
                   s.pricepaid,
                   s.currency,
                   s.transactionid,
                   u.firstname,
                   u.lastname,
                   u.email,
                   p.name AS planname
              FROM {user_subscription} s
              JOIN {user} u ON u.id = s.userid
              JOIN {subscription_plan} p ON p.id = s.planid
        ";
    }
}