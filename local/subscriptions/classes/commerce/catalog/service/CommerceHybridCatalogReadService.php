<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\legacy\CommerceLegacyCatalogReader;

/**
 * Native-first catalogue reader with an explicit Legacy compatibility fallback.
 */
final class CommerceHybridCatalogReadService {
    public function __construct(
        private readonly CommerceCatalogReadService $native,
        private readonly CommerceLegacyCatalogReader $legacy,
        private readonly bool $allowlegacyfallback = true
    ) {
    }

    public function find_by_sku(string $sku): ?CommerceProduct {
        $product = $this->native->find_by_sku($sku);
        if ($product !== null || !$this->allowlegacyfallback) {
            return $product;
        }

        return $this->legacy->find_by_sku($sku);
    }
}
