<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\flow;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical presentation identity for a Commerce purchase origin.
 *
 * The URL itself remains server-validated through PARAM_LOCALURL. This class
 * only normalises the semantic origin used by checkout/result UX.
 */
final class CommercePurchaseOrigin {
    public const STOREFRONT = 'storefront';
    public const PRODUCT = 'product';
    public const SHOWROOM = 'showroom';
    public const PERSONAL_OFFER = 'personaloffer';

    public static function normalise(string $source): string {
        $source = strtolower(trim($source));

        return match ($source) {
            self::STOREFRONT => self::STOREFRONT,
            self::PRODUCT => self::PRODUCT,
            self::SHOWROOM => self::SHOWROOM,
            self::PERSONAL_OFFER => self::PERSONAL_OFFER,
            default => '',
        };
    }

    public static function checkout_back_string(
        string $flow,
        string $source
    ): string {
        if (!CommercePurchaseFlow::is_direct($flow)) {
            return 'commerce_checkout_back_cart';
        }

        return match (self::normalise($source)) {
            self::STOREFRONT => 'commerce_checkout_back_storefront',
            self::PRODUCT => 'commerce_checkout_back_product',
            self::SHOWROOM => 'commerce_checkout_back_showroom',
            self::PERSONAL_OFFER => 'commerce_checkout_back_offer',
            default => 'commerce_checkout_back_offer',
        };
    }

    public static function result_back_string(
        string $source
    ): string {
        return match (self::normalise($source)) {
            self::STOREFRONT => 'commerce_checkout_back_storefront',
            self::PRODUCT => 'commerce_checkout_back_product',
            self::SHOWROOM => 'commerce_checkout_back_showroom',
            self::PERSONAL_OFFER => 'commerce_checkout_back_offer',
            default => 'commerce_checkout_back_storefront',
        };
    }
}
