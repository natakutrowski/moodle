<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

/** Resolves a Native purchase reference from a Legacy payment request identifier. */
final class CommerceShadowPurchaseReferenceResolver {
    public function resolve(int $legacyrequestid, ?string $legacyfamily = null): ?string {
        global $DB;

        if ($legacyrequestid <= 0) {
            return null;
        }

        $legacyfamily = $legacyfamily !== null ? strtolower(trim($legacyfamily)) : null;
        if ($legacyfamily !== null && !in_array($legacyfamily, ['subscription', 'digital'], true)) {
            throw new \coding_exception('Unsupported Commerce Legacy family: ' . $legacyfamily);
        }

        $params = ['legacyrequestid' => $legacyrequestid];
        $familyclause = '';
        if ($legacyfamily !== null) {
            $familyclause = ' AND p.legacyfamily = :legacyfamily';
            $params['legacyfamily'] = $legacyfamily;
        }

        $sql = "SELECT p.reference
                  FROM {local_subscriptions_commerce_payment} pay
                  JOIN {local_subscriptions_commerce_purchase} p ON p.id = pay.purchaseid
                 WHERE pay.legacyrequestid = :legacyrequestid" . $familyclause . "
              ORDER BY p.id DESC";

        $records = $DB->get_records_sql($sql, $params, 0, 2);
        if (count($records) !== 1) {
            return null;
        }

        $record = reset($records);
        return trim((string)$record->reference) ?: null;
    }
}
