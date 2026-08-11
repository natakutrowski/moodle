<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\ownership;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Resolves effective ownership across Native and transitional Legacy sources. */
final class CommerceStorefrontOwnershipResolver {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function owns(int $userid, string $sku): bool {
        return $this->resolve_source($userid, $sku) !== 'none';
    }

    public function resolve_source(int $userid, string $sku): string {
        $sku = strtoupper(trim($sku));
        if ($userid <= 0 || $sku === '') {
            return 'none';
        }

        if ($this->owns_native_grant($userid, $sku)) {
            return 'native_entitlement';
        }
        if ($this->owns_native_purchase($userid, $sku)) {
            return 'native_purchase';
        }

        $product = $this->db->get_record(
            'local_subs_commerce_product',
            ['sku' => $sku],
            'id,type'
        );
        if (!$product) {
            return 'none';
        }

        $type = strtolower((string)$product->type);
        if ($type === 'bundle' && $this->owns_bundle_components($userid, (int)$product->id)) {
            return 'bundle_components';
        }
        if (in_array($type, ['digital', 'digital_download'], true)) {
            return $this->owns_legacy_digital_product($userid, (int)$product->id)
                ? 'legacy_digital_purchase'
                : 'none';
        }

        $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
            'productid' => (int)$product->id,
            'legacytable' => 'subscription_plan',
        ], 'legacyid');
        if ($mapping && $this->owns_legacy_plan($userid, (int)$mapping->legacyid)) {
            return 'legacy_plan';
        }

        return 'none';
    }


    private function owns_bundle_components(int $userid, int $productid): bool {
        $sql = 'SELECT child.sku
                  FROM {local_subs_commerce_prod_comp} component
                  JOIN {local_subs_commerce_product} child ON child.id = component.childproductid
                 WHERE component.parentproductid = :productid
              ORDER BY component.sortorder, component.id';
        $skus = array_values($this->db->get_fieldset_sql($sql, ['productid' => $productid]));
        if ($skus === []) {
            return false;
        }
        foreach ($skus as $childsku) {
            if (!$this->owns($userid, (string)$childsku)) {
                return false;
            }
        }
        return true;
    }

    private function owns_native_purchase(int $userid, string $sku): bool {
        $sql = 'SELECT 1
                  FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p
                  JOIN {' . CommercePersistenceSchema::TABLE_ITEM . '} i ON i.purchaseid = p.id
                 WHERE p.userid = :userid
                   AND i.itemreference = :sku
                   AND p.status IN (:completed, :paid, :succeeded)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'sku' => $sku,
            'completed' => 'completed',
            'paid' => 'paid',
            'succeeded' => 'succeeded',
        ]);
    }

    private function owns_native_grant(int $userid, string $sku): bool {
        $now = time();
        $sql = 'SELECT 1
                  FROM {local_subs_commerce_grant}
                 WHERE beneficiaryuserid = :userid
                   AND productsku = :sku
                   AND status IN (:active, :granted, :completed)
                   AND validfrom <= :now
                   AND (validuntil IS NULL OR validuntil = 0 OR validuntil >= :now2)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'sku' => $sku,
            'active' => 'active',
            'granted' => 'granted',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
        ]);
    }

    private function owns_legacy_plan(int $userid, int $planid): bool {
        $now = time();
        $sql = 'SELECT 1
                  FROM {user_subscription}
                 WHERE userid = :userid
                   AND planid = :planid
                   AND status IN (:active, :completed)
                   AND start_date <= :now
                   AND (end_date = 0 OR end_date >= :now2)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'planid' => $planid,
            'active' => 'active',
            'completed' => 'completed',
            'now' => $now,
            'now2' => $now,
        ]);
    }

    private function owns_legacy_digital_product(int $userid, int $nativeproductid): bool {
        $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
            'productid' => $nativeproductid,
            'legacytable' => 'subscription_digital_product',
        ], 'legacyid');
        if (!$mapping) {
            return false;
        }

        $sql = 'SELECT 1
                  FROM {subscription_digital_payment_request}
                 WHERE userid = :userid
                   AND productid = :productid
                   AND status IN (:paid, :completed, :succeeded)';

        return $this->db->record_exists_sql($sql, [
            'userid' => $userid,
            'productid' => (int)$mapping->legacyid,
            'paid' => 'paid',
            'completed' => 'completed',
            'succeeded' => 'succeeded',
        ]);
    }
}
