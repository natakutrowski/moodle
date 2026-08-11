<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingConfiguration;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Blocking validation used before a product can become active. */
final class CommerceCatalogActivationValidator {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function validate(CommerceProduct $product): CommerceCatalogValidationResult {
        $issues = [];
        $productid = (int)$product->get_id();
        $activeprices = (new CommerceProductSellabilityInspector($this->db))->has_any_sellable_price($productid);
        $bundlecalculatedprice = $product->get_type() === CommerceProductType::BUNDLE
            && CommerceBundlePricingConfiguration::from_product($product)->get_strategy()
                !== CommerceBundlePricingStrategy::FIXED
            && $this->bundle_common_currencies($productid) !== [];

        if (!$activeprices && !$bundlecalculatedprice) {
            $issues[] = $this->error('no_active_price', 'commerce_validation_no_active_price');
        }

        if ($product->get_type() === CommerceProductType::COURSE_ACCESS) {
            $metadata = $product->get_metadata();
            $access = is_array($metadata['access'] ?? null) ? $metadata['access'] : [];
            $scopeid = (int)($access['scopeid'] ?? 0);

            // The effective Access Scope is the activation requirement. The
            // canonical Legacy mapping is migration metadata only and must not
            // block a new Native commercial product from being activated.
            if ($scopeid <= 0) {
                $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
                    'productid' => $productid,
                    'legacytable' => 'subscription_plan',
                ]);
                $plan = $mapping
                    ? $this->db->get_record('subscription_plan', ['id' => $mapping->legacyid])
                    : null;
                $scopeid = (int)($plan->accessscopeid ?? 0);
            }

            $scope = $scopeid > 0
                ? $this->db->get_record('subscription_access_scope', ['id' => $scopeid])
                : null;
            if (!$scope) {
                $issues[] = $this->error('missing_scope', 'commerce_validation_missing_scope');
            } else if ($this->parse_course_ids((string)$scope->course_ids) === []) {
                $issues[] = $this->error('empty_scope', 'commerce_validation_empty_scope');
            }
        }

        if ($product->get_type() === CommerceProductType::DIGITAL_DOWNLOAD) {
            $nativefiles = new CommerceCatalogDigitalFileManager(\context_system::instance());
            if (!$nativefiles->has_any_file($productid)) {
                // Compatibility fallback for products imported before H4.8.5.
                $mapping = $this->db->get_record('local_subs_commerce_prod_map', [
                    'productid' => $productid,
                    'legacytable' => 'subscription_digital_product',
                ]);
                $digital = $mapping
                    ? $this->db->get_record('subscription_digital_product', ['id' => $mapping->legacyid])
                    : null;
                if (!$digital || (trim((string)($digital->filename ?? '')) === ''
                        && trim((string)($digital->mobile_filename ?? '')) === '')) {
                    $issues[] = $this->error('missing_digital_file', 'commerce_validation_missing_digital_file');
                }
            }
        }

        if ($product->get_type() === CommerceProductType::BUNDLE) {
            $components = $this->db->get_records('local_subs_commerce_prod_comp', ['parentproductid' => $productid]);
            if (count($components) < 2) {
                $issues[] = $this->error('bundle_too_small', 'commerce_validation_bundle_too_small');
            }
            foreach ($components as $component) {
                $child = $this->db->get_record('local_subs_commerce_product', ['id' => $component->childproductid]);
                if (!$child || (string)$child->status !== 'active') {
                    $issues[] = $this->error('inactive_bundle_component', 'commerce_validation_inactive_bundle_component');
                    break;
                }
            }
        }

        return new CommerceCatalogValidationResult($issues);
    }

    /** @return string[] */
    private function bundle_common_currencies(int $productid): array {
        $components = $this->db->get_records(
            'local_subs_commerce_prod_comp',
            ['parentproductid' => $productid],
            'sortorder ASC'
        );
        if (count($components) < 2) {
            return [];
        }

        $common = null;
        foreach ($components as $component) {
            $child = $this->db->get_record(
                'local_subs_commerce_product',
                ['id' => $component->childproductid],
                'id,status'
            );
            if (!$child || (string)$child->status !== 'active') {
                return [];
            }

            $currencies = [];
            foreach ($this->db->get_records('local_subs_commerce_prod_price', [
                'productid' => (int)$child->id,
                'active' => 1,
            ]) as $price) {
                $currencies[strtoupper((string)$price->currency)] = true;
            }

            $common = $common === null ? $currencies : array_intersect_key($common, $currencies);
            if ($common === []) {
                return [];
            }
        }

        $result = array_keys($common ?? []);
        sort($result, SORT_STRING);
        return $result;
    }

    private function error(string $code, string $stringkey): CommerceCatalogValidationIssue {
        return new CommerceCatalogValidationIssue('error', $code, get_string($stringkey, 'local_subscriptions'));
    }

    /** @return int[] */
    private function parse_course_ids(string $value): array {
        $decoded = json_decode($value, true);
        $values = is_array($decoded) ? $decoded : preg_split('/[^0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique(array_filter(array_map('intval', $values ?: []), static fn(int $id): bool => $id > 0)));
    }
}
