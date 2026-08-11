<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\pricing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/**
 * Resolves the commercial credit granted for an already-owned source plan.
 *
 * Priority:
 * 1. latest fulfilled Native purchase amount for the mapped source product;
 * 2. Legacy source-plan price under the same Trial percentage;
 * 3. explicit fallback supplied by the caller.
 */
final class CommerceOwnedProductCreditResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function resolve(
        int $userid,
        int $sourceplanid,
        string $currency,
        int $trialpercent = 0,
        int $fallbackminor = 0
    ): int {
        $currency = strtoupper(trim($currency));

        if ($userid <= 0 || $sourceplanid <= 0 || $currency === '') {
            return max(0, $fallbackminor);
        }

        $nativeamount = $this->native_paid_amount(
            $userid,
            $sourceplanid,
            $currency
        );
        if ($nativeamount !== null) {
            return $nativeamount;
        }

        $legacyprice = $this->db->get_record(
            'subscription_plan_price',
            [
                'planid' => $sourceplanid,
                'currency' => $currency,
            ],
            'price',
            IGNORE_MISSING
        );

        if ($legacyprice !== false && $legacyprice !== null) {
            $amountminor = (int)round(
                ((float)$legacyprice->price) * 100
            );

            if ($trialpercent > 0 && $trialpercent < 100) {
                $discountminor = intdiv(
                    ($amountminor * $trialpercent) + 50,
                    100
                );
                $amountminor = max(0, $amountminor - $discountminor);
            }

            return $amountminor;
        }

        return max(0, $fallbackminor);
    }

    private function native_paid_amount(
        int $userid,
        int $sourceplanid,
        string $currency
    ): ?int {
        $mapping = $this->db->get_record_sql(
            "SELECT p.sku
               FROM {local_subs_commerce_prod_map} m
               JOIN {local_subs_commerce_product} p
                 ON p.id = m.productid
              WHERE m.legacytable = :legacytable
                AND m.legacyid = :legacyid",
            [
                'legacytable' => 'subscription_plan',
                'legacyid' => $sourceplanid,
            ],
            IGNORE_MISSING
        );

        if (!$mapping || trim((string)$mapping->sku) === '') {
            return null;
        }

        $records = $this->db->get_records_sql(
            "SELECT i.*
               FROM {" . CommercePersistenceSchema::TABLE_ITEM . "} i
               JOIN {" . CommercePersistenceSchema::TABLE_PURCHASE . "} p
                 ON p.id = i.purchaseid
              WHERE p.userid = :userid
                AND p.status = :status
                AND i.itemreference = :itemreference
                AND i.currency = :currency
           ORDER BY p.timecreated DESC, i.id DESC",
            [
                'userid' => $userid,
                'status' => 'fulfilled',
                'itemreference' => strtoupper(trim((string)$mapping->sku)),
                'currency' => $currency,
            ],
            0,
            1
        );

        $item = $records ? reset($records) : false;
        if (!$item) {
            return null;
        }

        $metadata = $this->decode((string)$item->metadatajson);
        $pricing = $this->decode((string)$item->pricingjson);

        foreach ([
            $metadata['pricing_final_unit_minor'] ?? null,
            $pricing['pricing_final_unit_minor'] ?? null,
            $metadata['locked_payable_unit_minor'] ?? null,
            $item->unitminor ?? null,
        ] as $candidate) {
            if (is_numeric($candidate) && (int)$candidate >= 0) {
                return (int)$candidate;
            }
        }

        return null;
    }

    /** @return array<string,mixed> */
    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
