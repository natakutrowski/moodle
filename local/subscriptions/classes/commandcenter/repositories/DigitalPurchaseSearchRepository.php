<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

final class DigitalPurchaseSearchRepository {

    public function search(string $text, int $limit = 10): array {
        global $DB;

        $text = trim($text);

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        $like = '%' . $DB->sql_like_escape($text) . '%';

        $searchfield = $DB->sql_concat(
            'r.email', "' '",
            'r.firstname', "' '",
            'r.lastname', "' '",
            'r.transactionid', "' '",
            'r.sessionid', "' '",
            'p.name'
        );

        $likesql = $DB->sql_like($searchfield, ':query', false, false);

        $sql = $this->base_sql() . "
             WHERE $likesql
          ORDER BY r.creation_date DESC
        ";

        return array_values($DB->get_records_sql($sql, ['query' => $like], 0, $limit));
    }

    public function find_by_id(int $id): ?object {
        global $DB;

        $sql = $this->base_sql() . "
             WHERE r.id = :id
        ";

        $purchase = $DB->get_record_sql($sql, ['id' => $id]);

        return $purchase ?: null;
    }

    private function base_sql(): string {
        return "
            SELECT r.id,
                   r.email,
                   r.firstname,
                   r.lastname,
                   r.currency,
                   r.price,
                   r.status,
                   r.payment_provider,
                   r.transactionid,
                   r.creation_date,
                   p.name AS productname
              FROM {subscription_digital_payment_request} r
              JOIN {subscription_digital_product} p ON p.id = r.productid
        ";
    }
}