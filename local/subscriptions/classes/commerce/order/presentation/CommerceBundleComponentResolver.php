<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\presentation;

defined('MOODLE_INTERNAL') || die();

use moodle_database;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;

/** Resolves the customer-facing composition of Native bundle purchase items. */
final class CommerceBundleComponentResolver {
    public function __construct(
        private readonly moodle_database $database,
        private readonly ?CommerceProductDisplayNameResolver $displaynames = null
    ) {
    }

    public function resolve(CommerceOrderItemPresentation $item): array {
        $candidates = array_filter(array_unique([
            trim((string)($item->metadata['productsku'] ?? '')),
            trim((string)($item->metadata['sku'] ?? '')),
            trim($item->reference),
        ]));
        foreach ($item->accesses as $access) {
            $candidate = trim((string)($access->metadata['productsku'] ?? ''));
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }

        $parent = false;
        foreach (array_unique($candidates) as $sku) {
            $candidate = $this->database->get_record(
                'local_subs_commerce_product',
                ['sku' => $sku],
                'id,sku,type',
                IGNORE_MISSING
            );
            if ($candidate !== false && (string)$candidate->type === 'bundle') {
                $parent = $candidate;
                break;
            }
        }
        if ($parent === false) {
            return [];
        }

        $sql = "SELECT c.id, c.quantity, p.id AS productid, p.sku, p.name, p.type
                  FROM {local_subs_commerce_prod_comp} c
                  JOIN {local_subs_commerce_product} p ON p.id = c.childproductid
                 WHERE c.parentproductid = :parentid
              ORDER BY c.sortorder ASC, c.id ASC";

        $displaynames = $this->displaynames
            ?? CommerceProductDisplayNameResolver::create($this->database);

        $components = [];
        foreach ($this->database->get_records_sql($sql, [
            'parentid' => (int)$parent->id,
        ]) as $record) {
            $components[] = [
                'name' => $displaynames->resolve(
                    [(string)$record->sku],
                    current_language(),
                    (string)$record->name
                ),
                'sku' => (string)$record->sku,
                'type' => (string)$record->type,
                'quantity' => (int)$record->quantity,
                'iscourse' => in_array((string)$record->type, ['course_access', 'subscription', 'course'], true),
                'isdigital' => in_array((string)$record->type, ['digital_download', 'digital'], true),
            ];
        }
        return $components;
    }
}
