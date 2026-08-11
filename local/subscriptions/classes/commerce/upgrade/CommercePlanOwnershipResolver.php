<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\upgrade;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver;
use local_subscriptions\constants\Status;

/** Resolves effective ownership of Legacy plans across Legacy subscriptions and Native products. */
final class CommercePlanOwnershipResolver {
    private readonly CommerceStorefrontOwnershipResolver $ownership;

    public function __construct(private readonly \moodle_database $db) {
        $this->ownership = new CommerceStorefrontOwnershipResolver($db);
    }

    /**
     * @return array{planid:int,source:string,subscriptionid:?int,startdate:int,enddate:int}|null
     */
    public function resolve(int $userid, int $planid): ?array {
        if ($userid <= 0 || $planid <= 0) {
            return null;
        }

        $now = time();
        $legacy = $this->db->get_record_sql(
            "SELECT id, planid, start_date, end_date
               FROM {user_subscription}
              WHERE userid = :userid
                AND planid = :planid
                AND status IN (:active, :completed)
                AND start_date <= :now
                AND (end_date = 0 OR end_date >= :now2)
           ORDER BY end_date DESC, id DESC",
            [
                'userid' => $userid,
                'planid' => $planid,
                'active' => Status::ACTIVE,
                'completed' => 'completed',
                'now' => $now,
                'now2' => $now,
            ],
            IGNORE_MISSING
        );
        if ($legacy) {
            return [
                'planid' => $planid,
                'source' => 'legacy_subscription',
                'subscriptionid' => (int)$legacy->id,
                'startdate' => (int)$legacy->start_date,
                'enddate' => (int)$legacy->end_date,
            ];
        }

        $nativegrant = $this->native_grant_for_source_plan($userid, $planid, $now);
        if ($nativegrant !== null) {
            return [
                'planid' => $planid,
                'source' => 'native_grant',
                'subscriptionid' => null,
                'startdate' => (int)$nativegrant->validfrom,
                'enddate' => $nativegrant->validuntil === null ? 0 : (int)$nativegrant->validuntil,
            ];
        }

        $product = $this->mapped_product($planid);
        if (!$product || !$this->ownership->owns($userid, (string)$product->sku)) {
            return null;
        }

        return [
            'planid' => $planid,
            'source' => 'native_product',
            'subscriptionid' => null,
            'startdate' => $now,
            'enddate' => 0,
        ];
    }


    private function native_grant_for_source_plan(int $userid, int $planid, int $now): ?\stdClass {
        $records = $this->db->get_records_select(
            'local_subs_commerce_grant',
            'beneficiaryuserid = :userid'
                . ' AND type = :type'
                . ' AND status IN (:active, :granted, :completed)'
                . ' AND validfrom <= :now'
                . ' AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)',
            [
                'userid' => $userid,
                'type' => 'course_access',
                'active' => 'active',
                'granted' => 'granted',
                'completed' => 'completed',
                'now' => $now,
                'now2' => $now,
            ],
            'timemodified DESC, id DESC',
            'id,validfrom,validuntil,configurationjson,metadatajson'
        );

        foreach ($records as $record) {
            foreach ([(string)($record->configurationjson ?? ''), (string)($record->metadatajson ?? '')] as $json) {
                $payload = json_decode($json, true);
                if (!is_array($payload)) {
                    continue;
                }
                $sourceplanid = (int)($payload['sourceplanid']
                    ?? $payload['source_plan_id']
                    ?? $payload['legacyplanid']
                    ?? 0);
                if ($sourceplanid === $planid) {
                    return $record;
                }
            }
        }

        return null;
    }

    private function mapped_product(int $planid): ?\stdClass {
        $mapped = $this->db->get_record_sql(
            "SELECT p.id, p.sku
               FROM {local_subs_commerce_prod_map} m
               JOIN {local_subs_commerce_product} p ON p.id = m.productid
              WHERE m.legacytable = :legacytable
                AND m.legacyid = :legacyid",
            ['legacytable' => 'subscription_plan', 'legacyid' => $planid],
            IGNORE_MISSING
        );
        if ($mapped) {
            return $mapped;
        }

        foreach ($this->db->get_records('local_subs_commerce_product', null, 'id ASC', 'id,sku,metadatajson') as $product) {
            $metadata = json_decode((string)($product->metadatajson ?? ''), true);
            if (!is_array($metadata)) {
                continue;
            }
            if (strtolower(trim((string)($metadata['legacyfamily'] ?? ''))) === 'subscription'
                    && (int)($metadata['legacyid'] ?? 0) === $planid) {
                return $product;
            }
        }

        return $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => 'SUB.PLAN.' . $planid],
            'id,sku',
            IGNORE_MISSING
        ) ?: null;
    }
}
