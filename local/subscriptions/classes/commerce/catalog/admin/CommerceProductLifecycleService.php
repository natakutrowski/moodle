<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

/** Safe product lifecycle operations for catalogue maintenance and DEV cleanup. */
final class CommerceProductLifecycleService {
    private const SUCCESS_STATUSES = ['paid', 'completed', 'succeeded'];

    public function __construct(private readonly \moodle_database $db) {
    }

    /** @return array<string,int> */
    public function dependency_counts(int $productid, string $sku): array {
        $references = $this->native_item_references($productid, $sku);
        [$insql, $params] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'ref');
        $nativeitems = $this->db->count_records_select(
            'local_subscriptions_commerce_purchase_item',
            "itemreference {$insql}",
            $params
        );

        $nativepurchases = 0;
        if ($nativeitems > 0) {
            $nativepurchases = (int)$this->db->count_records_sql(
                "SELECT COUNT(DISTINCT pi.purchaseid)
                   FROM {local_subscriptions_commerce_purchase_item} pi
                  WHERE pi.itemreference {$insql}",
                $params
            );
        }

        $legacyplansales = 0;
        $legacydigitalsales = 0;
        foreach ($this->db->get_records('local_subs_commerce_prod_map', ['productid' => $productid]) as $mapping) {
            if ((string)$mapping->legacytable === 'subscription_plan') {
                [$statussql, $statusparams] = $this->db->get_in_or_equal(self::SUCCESS_STATUSES, SQL_PARAMS_NAMED, 's');
                $legacyplansales += $this->db->count_records_select(
                    'subscription_payment_request',
                    "planid = :planid AND status {$statussql}",
                    ['planid' => (int)$mapping->legacyid] + $statusparams
                );
            }
            if ((string)$mapping->legacytable === 'subscription_digital_product') {
                [$statussql, $statusparams] = $this->db->get_in_or_equal(self::SUCCESS_STATUSES, SQL_PARAMS_NAMED, 'd');
                $legacydigitalsales += $this->db->count_records_select(
                    'subscription_digital_payment_request',
                    "productid = :digitalid AND status {$statussql}",
                    ['digitalid' => (int)$mapping->legacyid] + $statusparams
                );
            }
        }

        return [
            'prices' => $this->db->count_records('local_subs_commerce_prod_price', ['productid' => $productid]),
            'translations' => $this->db->count_records('local_subs_commerce_prod_tr', ['productid' => $productid]),
            'components' => $this->db->count_records_select(
                'local_subs_commerce_prod_comp',
                'parentproductid = ? OR childproductid = ?',
                [$productid, $productid]
            ),
            'entitlements' => $this->db->count_records('local_subs_commerce_prod_ent', ['productid' => $productid]),
            'mappings' => $this->db->count_records('local_subs_commerce_prod_map', ['productid' => $productid]),
            'nativepurchaseitems' => $nativeitems,
            'nativepurchases' => $nativepurchases,
            'legacyplansales' => $legacyplansales,
            'legacydigitalsales' => $legacydigitalsales,
            'grants' => $this->db->count_records_select(
                'local_subs_commerce_grant',
                "productsku {$insql}",
                $params
            ),
        ];
    }

    public function can_delete_without_sales(int $productid, string $sku): bool {
        $counts = $this->dependency_counts($productid, $sku);
        return $counts['nativepurchaseitems'] === 0
            && $counts['legacyplansales'] === 0
            && $counts['legacydigitalsales'] === 0
            && $counts['grants'] === 0;
    }

    public function delete(int $productid, string $sku, bool $force = false): void {
        if (!$force && !$this->can_delete_without_sales($productid, $sku)) {
            throw new \coding_exception('A product with sales or grants cannot be deleted without destructive confirmation.');
        }

        $transaction = $this->db->start_delegated_transaction();
        try {
            if ($force) {
                $references = $this->native_item_references($productid, $sku);
                [$insql, $params] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'delref');
                $items = $this->db->get_records_select(
                    'local_subscriptions_commerce_purchase_item',
                    "itemreference {$insql}",
                    $params,
                    '',
                    'id,purchaseid'
                );
                $purchaseids = [];
                foreach ($items as $item) {
                    $purchaseids[(int)$item->purchaseid] = true;
                }
                $this->db->delete_records_select(
                    'local_subscriptions_commerce_purchase_item',
                    "itemreference {$insql}",
                    $params
                );
                foreach (array_keys($purchaseids) as $purchaseid) {
                    if (!$this->db->record_exists('local_subscriptions_commerce_purchase_item', ['purchaseid' => $purchaseid])) {
                        $this->db->delete_records('local_subscriptions_commerce_purchase', ['id' => $purchaseid]);
                    }
                }
                $this->db->delete_records_select('local_subs_commerce_grant', "productsku {$insql}", $params);
            }

            $this->db->delete_records_select(
                'local_subs_commerce_prod_comp',
                'parentproductid = ? OR childproductid = ?',
                [$productid, $productid]
            );
            foreach (['local_subs_commerce_prod_price', 'local_subs_commerce_prod_tr',
                'local_subs_commerce_prod_ent', 'local_subs_commerce_prod_map'] as $table) {
                $this->db->delete_records($table, ['productid' => $productid]);
            }
            $this->db->delete_records('local_subs_commerce_product', ['id' => $productid]);
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    /** @return string[] */
    private function native_item_references(int $productid, string $sku): array {
        $references = [strtoupper(trim($sku)), trim($sku)];
        foreach ($this->db->get_records('local_subs_commerce_prod_map', ['productid' => $productid]) as $mapping) {
            $legacyid = (int)$mapping->legacyid;
            if ((string)$mapping->legacytable === 'subscription_plan') {
                $references[] = 'subscription-plan:' . $legacyid;
                $references[] = 'SUB.PLAN.' . $legacyid;
            } else if ((string)$mapping->legacytable === 'subscription_digital_product') {
                $references[] = 'digital-product:' . $legacyid;
                $references[] = 'DIGITAL.PRODUCT.' . $legacyid;
            }
        }
        return array_values(array_unique(array_filter($references, static fn(string $value): bool => $value !== '')));
    }
}
