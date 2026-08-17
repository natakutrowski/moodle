<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\catalog;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\exception\CommerceCartCatalogException;
use local_subscriptions\commerce\cart\policy\CommerceQuantityPolicyResolver;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\catalog\repository\CommerceProductTranslationRepository;
use local_subscriptions\commerce\pricing\CommerceProductPromotionService;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** Native catalogue adapter used by the session cart runtime. */
final class MoodleCommerceCartCatalogGateway implements CommerceCartCatalogGateway {
    public function __construct(
        private readonly CommerceProductRepository $products,
        private readonly CommerceProductPriceRepository $prices,
        private readonly CommerceProductTranslationRepository $translations,
        private readonly CommerceQuantityPolicyResolver $quantitypolicies
    ) {
    }

    public function quote(
        string $productsku,
        int $priceid,
        string $currency,
        string $language,
        ?int $at = null
    ): CommerceCartProductQuote {
        $product = $this->products->find_by_sku($productsku);
        $price = $this->prices->find_by_id($priceid);

        if ($product === null || $price === null) {
            throw new CommerceCartCatalogException('The cart product or price no longer exists.');
        }

        if (!$product->is_available_at($at ?? time()) || !$price->is_active()) {
            throw new CommerceCartCatalogException('The cart product or price is no longer available.');
        }

        if ($price->get_product_sku() !== $product->get_sku()) {
            throw new CommerceCartCatalogException('The selected price does not belong to the cart product.');
        }

        if ($price->get_currency() !== strtoupper(trim($currency))) {
            throw new CommerceCartCatalogException('The selected price does not match the cart currency.');
        }

        $translation = $this->translations->find($product->get_sku(), $language);

        $promotion = (new CommerceProductPromotionService())->resolve(
            $product->get_metadata(),
            $price->get_currency(),
            $price->get_amount_minor(),
            $at ?? time()
        );
        $money = CommerceMoney::from_minor(
            $promotion['amountminor'] ?? $price->get_amount_minor(),
            $price->get_currency()
        );

        return new CommerceCartProductQuote(
            $product->get_sku(),
            (int)$price->get_id(),
            $translation?->get_name() ?? $product->get_name(),
            $money,
            $this->quantitypolicies->resolve($product),
            $product->get_type()
        );
    }
}
