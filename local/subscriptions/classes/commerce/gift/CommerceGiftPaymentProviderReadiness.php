<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\repository\CommerceProductPriceRepository;

/**
 * G5B.4B4: preparation-only currency / price / provider policy.
 * No payment transaction, public feature enablement or rights operation.
 * Real checkout availability still belongs to Commerce Native's method router.
 */
final class CommerceGiftPaymentProviderReadiness {
    public const SKU = 'AVENT_2026';

    /** Expected existing Native routing, NOT a browser-selected PSP. */
    public static function route_for_currency(string $currency): string {
        return match (strtoupper(trim($currency))) {
            'EUR' => 'stripe',
            'RUB' => 'alfa',
            default => throw new \DomainException('Currency is not enabled for Calendar gifts.'),
        };
    }

    /**
     * Only the Native catalogue determines the chargeable price. We never
     * trust a price ID, amount or provider transmitted by the showroom/form.
     */
    public static function resolve_price(CommerceProductPriceRepository $prices,
        string $currency): CommerceProductPrice {
        $currency = strtoupper(trim($currency));
        $provider = self::route_for_currency($currency);
        $price = $prices->find_active(self::SKU, $currency, $provider);
        if ($price === null) {
            throw new \DomainException('No active Native price for the requested currency and provider.');
        }
        self::assert_price($price, $currency, $provider);
        return $price;
    }

    public static function assert_price(CommerceProductPrice $price, string $currency,
        string $provider): void {
        $currency = strtoupper(trim($currency));
        $provider = strtolower(trim($provider));
        $expected = self::route_for_currency($currency);
        $actualprovider = $price->get_provider();
        if ($provider !== $expected || !$price->is_active()
            || $price->get_id() === null || $price->get_id() <= 0
            || $price->get_product_sku() !== self::SKU
            || $price->get_currency() !== $currency
            || $price->get_amount_minor() <= 0
            || ($actualprovider !== null && $actualprovider !== $expected)) {
            throw new \DomainException('Native gift price or provider does not match the trusted context.');
        }
    }
}
