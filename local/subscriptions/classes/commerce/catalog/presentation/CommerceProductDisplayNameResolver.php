<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\presentation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use moodle_database;

/**
 * Resolve a customer-facing Native Commerce product title.
 *
 * This intentionally reuses the exact catalogue translation repository used by
 * Cart/Checkout. Multiple SKU candidates are tried because historical purchase
 * rows do not all persist the product SKU in the same field.
 */
final class CommerceProductDisplayNameResolver {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductTranslationRepository $translations
    ) {
    }

    public static function create(moodle_database $database): self {
        $hydrator = new CommerceCatalogHydrator();
        $products = new CommerceProductRepository($database, $hydrator);

        return new self(
            $products,
            new CommerceProductTranslationRepository(
                $database,
                $hydrator,
                $products
            )
        );
    }

    /**
     * @param string[] $skucandidates
     */
    public function resolve(
        array $skucandidates,
        string $language,
        string $fallback = ''
    ): string {
        $seen = [];

        foreach ($skucandidates as $candidate) {
            $sku = strtoupper(trim($candidate));
            if ($sku === '' || isset($seen[$sku])) {
                continue;
            }
            $seen[$sku] = true;

            $product = $this->products->find_by_sku($sku);
            if ($product === null) {
                continue;
            }

            $translation = $this->translations->find(
                $product->get_sku(),
                $language
            );

            if ($translation !== null && trim($translation->get_name()) !== '') {
                return CommerceProductDisplayText::title(
                    $translation->get_name()
                );
            }

            if (trim($product->get_name()) !== '') {
                return CommerceProductDisplayText::title(
                    $product->get_name()
                );
            }
        }

        return CommerceProductDisplayText::title($fallback);
    }
}
