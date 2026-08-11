<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;

/**
 * Builds safe product selectors for Showroom.
 */
final class CommerceShowroomProductLinkOptions {
    private CommerceProductRepository $products;

    public function __construct(\moodle_database $db) {
        $this->products = new CommerceProductRepository(
            $db,
            new CommerceCatalogHydrator()
        );
    }

    /**
     * @return array<string,array<string,string>>
     */
    public function grouped_options(array $current = []): array {
        return [
            'course' => $this->options_for_type(
                CommerceProductType::COURSE_ACCESS,
                (string)($current['course'] ?? '')
            ),
            'pdf' => $this->options_for_type(
                CommerceProductType::DIGITAL_DOWNLOAD,
                (string)($current['pdf'] ?? '')
            ),
            'bundle' => $this->options_for_type(
                CommerceProductType::BUNDLE,
                (string)($current['bundle'] ?? '')
            ),
        ];
    }

    /**
     * Validate a selected SKU while allowing an empty relationship.
     */
    public function normalise_sku(string $sku, string $expectedtype): string {
        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            return '';
        }

        $product = $this->products->find_by_sku($sku);
        if ($product === null) {
            throw new \invalid_parameter_exception('Unknown linked Commerce product: ' . $sku);
        }
        if ($product->get_type() !== $expectedtype) {
            throw new \invalid_parameter_exception(
                'Linked Commerce product has an incompatible product type: ' . $sku
            );
        }

        return $product->get_sku();
    }

    /** @return array<string,string> */
    private function options_for_type(string $type, string $current): array {
        $options = [];
        foreach ($this->products->find_by_type($type) as $product) {
            $suffix = $product->is_active() ? '' : ' · ' . $product->get_status();
            $options[$product->get_sku()] = $product->get_name()
                . ' — ' . $product->get_sku()
                . $suffix;
        }

        // Preserve visibility of an old/legacy relationship that is not
        // present in the Native catalogue yet. Saving is blocked unless the
        // selection points to a real compatible Native product.
        $current = strtoupper(trim($current));
        if ($current !== '' && !isset($options[$current])) {
            $options[$current] = $current . ' — association actuelle (hors catalogue Native)';
        }

        return $options;
    }
}
