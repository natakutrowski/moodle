<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductEntitlementRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;

final class CommerceProductAdminService {
    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations,
        private readonly CommerceProductComponentRepository $components,
        private readonly CommerceProductEntitlementRepository $entitlements
    ) {
    }

    public function save_product(CommerceProduct $product): CommerceProduct {
        return $this->products->save($product);
    }

    public function set_price(CommerceProductPrice $price): CommerceProductPrice {
        return $this->prices->save($price);
    }

    public function set_translation(CommerceProductTranslation $translation): CommerceProductTranslation {
        return $this->translations->save($translation);
    }

    public function replace_definition(string $sku, array $components, array $entitlements): void {
        $transaction = $this->db->start_delegated_transaction();

        $this->components->replace_for_parent($sku, $components);
        $this->entitlements->replace_for_product($sku, $entitlements);

        $transaction->allow_commit();
    }
}
