<?php

namespace local_subscriptions\commandcenter\repositories;

defined('MOODLE_INTERNAL') || die();

final class DigitalProductSearchRepository {

    public function search(string $text, int $limit = 10): array {
        global $DB;

        $text = trim($text);

        if (\core_text::strlen($text) < 2) {
            return [];
        }

        $like = '%' . $DB->sql_like_escape($text) . '%';

        $searchfield = $DB->sql_concat(
            'p.name', "' '",
            'p.slug', "' '",
            'p.filename'
        );

        $likesql = $DB->sql_like($searchfield, ':query', false, false);

        $sql = "
            SELECT p.id, p.name, p.slug, p.filename, p.enabled, p.price_eur, p.price_rub
              FROM {subscription_digital_product} p
             WHERE $likesql
          ORDER BY p.enabled DESC, p.sortorder ASC, p.name ASC
        ";

        return array_values($DB->get_records_sql($sql, ['query' => $like], 0, $limit));
    }

    public function find_by_id(int $id): ?object {
        global $DB;

        $product = $DB->get_record('subscription_digital_product', [
            'id' => $id,
        ], 'id, name, slug, filename, enabled, price_eur, price_rub');

        return $product ?: null;
    }
}