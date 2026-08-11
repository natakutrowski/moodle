<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\admin;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionResult;
use local_subscriptions\commerce\bundle\expansion\CommerceBundleExpansionService;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductComponent;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\catalog\service\CommerceProductAdminService;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

/**
 * Application backend for the future unified CRM Commerce Product Manager.
 */
final class CommerceCatalogProductManager {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations,
        private readonly CommerceProductComponentRepository $components,
        private readonly CommerceProductEntitlementRepository $entitlements,
        private readonly CommerceProductAdminService $admin,
        private readonly CommerceBundleExpansionService $expander,
        private readonly CommerceProductTypeRegistry $types
    ) {
    }

    /**
     * @return CommerceCatalogProductSummary[]
     */
    public function list_products(?string $type = null, ?string $status = null): array {
        $products = $this->products->find_all($type, $status);
        $counts = $this->load_counts();

        return array_map(
            static function(CommerceProduct $product) use ($counts): CommerceCatalogProductSummary {
                $id = (int) $product->get_id();

                return new CommerceCatalogProductSummary(
                    $product,
                    $counts['prices'][$id] ?? 0,
                    $counts['translations'][$id] ?? 0,
                    $counts['components'][$id] ?? 0,
                    $counts['entitlements'][$id] ?? 0
                );
            },
            $products
        );
    }

    public function save_product_input(CommerceCatalogProductInput $input, ?string $originalsku = null): CommerceCatalogProductEditorData {
        $existing = null;

        if ($originalsku !== null && trim($originalsku) !== '') {
            $existing = $this->require_product($originalsku);
        }

        $product = $input->to_product($existing);

        if ($existing !== null && $existing->get_sku() !== $product->get_sku()) {
            throw new \coding_exception('A Commerce product SKU cannot be changed after creation.');
        }

        if (!$this->types->has($product->get_type())) {
            throw new \coding_exception('Unknown Commerce product type: ' . $product->get_type());
        }

        $saved = $this->admin->save_product($product);

        return $this->get_editor_data($saved->get_sku());
    }

    /**
     * @param array<int, CommerceProductTranslation> $translations
     */
    public function save_price(\local_subscriptions\commerce\catalog\domain\CommerceProductPrice $price): void {
        $this->require_product($price->get_product_sku());
        $this->admin->set_price($price);
    }

    public function update_price(\local_subscriptions\commerce\catalog\domain\CommerceProductPrice $price): void {
        $this->require_product($price->get_product_sku());
        $this->prices->update_by_id($price);
    }

    public function delete_price(string $sku, int $priceid): void {
        $this->require_product($sku);
        $this->prices->delete_by_id($sku, $priceid);
    }

    public function price_currency_exists(string $sku, string $currency, ?int $excludeid = null): bool {
        return $this->prices->currency_exists($sku, $currency, $excludeid);
    }

    /** @param array<int, \local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition> $definitions */
    public function save_entitlements(string $sku, array $definitions): void {
        $this->require_product($sku);
        $this->entitlements->replace_for_product($sku, $definitions);
    }

    public function save_metadata(string $sku, array $metadata): CommerceCatalogProductEditorData {
        $product = $this->require_product($sku);
        $updated = new CommerceProduct(
            $product->get_sku(),
            $product->get_type(),
            $product->get_status(),
            $product->get_name(),
            $product->get_description(),
            $metadata,
            $product->get_id(),
            $product->get_available_from(),
            $product->get_available_until(),
            $product->get_time_created(),
            $product->get_time_modified()
        );
        $this->admin->save_product($updated);
        return $this->get_editor_data($sku);
    }

    public function save_translations(string $sku, array $translations): void {
        $this->require_product($sku);

        foreach ($translations as $translation) {
            if (!$translation instanceof CommerceProductTranslation) {
                throw new \coding_exception('Invalid Commerce product translation collection.');
            }
            if ($translation->get_product_sku() !== strtoupper(trim($sku))) {
                throw new \coding_exception('A Commerce product translation has the wrong product SKU.');
            }
            $this->admin->set_translation($translation);
        }
    }

    public function set_status(string $sku, string $status): CommerceCatalogProductEditorData {
        $product = $this->require_product($sku);
        $updated = new CommerceProduct(
            $product->get_sku(),
            $product->get_type(),
            $status,
            $product->get_name(),
            $product->get_description(),
            $product->get_metadata(),
            $product->get_id(),
            $product->get_available_from(),
            $product->get_available_until(),
            $product->get_time_created(),
            $product->get_time_modified()
        );
        $this->admin->save_product($updated);
        return $this->get_editor_data($sku);
    }

    public function archive_product(string $sku): CommerceCatalogProductEditorData {
        $product = $this->require_product($sku);
        $archived = new CommerceProduct(
            $product->get_sku(),
            $product->get_type(),
            'archived',
            $product->get_name(),
            $product->get_description(),
            $product->get_metadata(),
            $product->get_id(),
            $product->get_available_from(),
            $product->get_available_until(),
            $product->get_time_created(),
            $product->get_time_modified()
        );
        $this->admin->save_product($archived);

        return $this->get_editor_data($sku);
    }

    public function get_editor_data(string $sku): CommerceCatalogProductEditorData {
        $product = $this->require_product($sku);
        $expansion = null;

        if ($product->is_bundle()) {
            try {
                $expansion = $this->expander->expand($product->get_sku(), 1, true);
            } catch (\Throwable) {
                // Draft and incomplete bundles must remain editable. Strict
                // validation is applied by preview_bundle() and save_bundle().
                $expansion = null;
            }
        }

        return new CommerceCatalogProductEditorData(
            $product,
            $this->prices->find_by_product_sku($product->get_sku()),
            $this->translations->find_by_product_sku($product->get_sku()),
            $this->components->find_by_parent_sku($product->get_sku()),
            $this->entitlements->find_by_product_sku($product->get_sku()),
            $expansion
        );
    }

    /**
     * Saves a bundle product and its complete composition atomically.
     *
     * @param CommerceProductComponent[] $components
     */
    public function save_bundle(CommerceProduct $product, array $components): CommerceCatalogProductEditorData {
        if ($product->get_type() !== CommerceProductType::BUNDLE) {
            throw new \coding_exception('The CRM bundle editor only accepts bundle products.');
        }

        if (!$this->types->has(CommerceProductType::BUNDLE)) {
            throw new \RuntimeException('The Commerce bundle product type is not registered.');
        }

        $this->validate_component_collection($product->get_sku(), $components);
        $transaction = $this->db->start_delegated_transaction();

        try {
            $saved = $this->admin->save_product($product);
            $this->admin->replace_definition(
                $saved->get_sku(),
                $components,
                []
            );

            // Expansion is the final integrity gate. It detects empty bundles,
            // inactive descendants and cycles before the transaction commits.
            $this->expander->expand($saved->get_sku(), 1, true);
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }

        return $this->get_editor_data($product->get_sku());
    }

    public function preview_bundle(string $sku): CommerceBundleExpansionResult {
        $product = $this->require_product($sku);

        if (!$product->is_bundle()) {
            throw new \coding_exception('Only a bundle can be previewed as a composition.');
        }

        return $this->expander->expand($product->get_sku(), 1, true);
    }

    private function require_product(string $sku): CommerceProduct {
        $product = $this->products->find_by_sku($sku);

        if ($product === null) {
            throw new \coding_exception('Unknown Commerce product: ' . strtoupper(trim($sku)));
        }

        return $product;
    }

    private function validate_component_collection(string $parentsku, array $components): void {
        if ($components === []) {
            throw new \coding_exception('A Commerce bundle must contain at least one component.');
        }

        $seen = [];

        foreach ($components as $component) {
            if (!$component instanceof CommerceProductComponent) {
                throw new \coding_exception('Invalid Commerce bundle component collection.');
            }

            if ($component->get_parent_product_sku() !== strtoupper(trim($parentsku))) {
                throw new \coding_exception('A Commerce bundle component has the wrong parent SKU.');
            }

            $childsku = $component->get_child_product_sku();

            if (isset($seen[$childsku])) {
                throw new \coding_exception('A Commerce bundle cannot contain the same component twice.');
            }

            $seen[$childsku] = true;
            $child = $this->products->find_by_sku($childsku);

            if ($child === null) {
                throw new \coding_exception('Unknown Commerce bundle component: ' . $childsku);
            }

            if (!$child->is_active()) {
                throw new \coding_exception('Inactive Commerce bundle component: ' . $childsku);
            }
        }
    }

    private function load_counts(): array {
        return [
            'prices' => $this->count_by_product('local_subs_commerce_prod_price', 'productid'),
            'translations' => $this->count_by_product('local_subs_commerce_prod_tr', 'productid'),
            'components' => $this->count_by_product('local_subs_commerce_prod_comp', 'parentproductid'),
            'entitlements' => $this->count_by_product('local_subs_commerce_prod_ent', 'productid'),
        ];
    }

    private function count_by_product(string $table, string $field): array {
        $sql = "SELECT {$field} AS productid, COUNT(1) AS itemcount
                  FROM {{$table}}
              GROUP BY {$field}";
        $records = $this->db->get_records_sql($sql);
        $counts = [];

        foreach ($records as $record) {
            $counts[(int) $record->productid] = (int) $record->itemcount;
        }

        return $counts;
    }
}
