<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\cover;

defined('MOODLE_INTERNAL') || die();

/** Stable customer-facing usages for product artwork. */
final class CommerceProductCoverContext {
    public const STOREFRONT = 'storefront';
    public const PRODUCT = 'product';
    public const RECOMMENDATION = 'recommendation';
    public const RESOURCES = 'resources';
    public const CHECKOUT = 'checkout';
    public const EMAIL = 'email';
    public const SOCIAL = 'social';
    public const SHOWROOM = 'showroom';
    public const DEFAULT = 'cover';

    /** @return string[] */
    public static function all(): array {
        return [
            self::STOREFRONT,
            self::PRODUCT,
            self::RECOMMENDATION,
            self::RESOURCES,
            self::CHECKOUT,
            self::EMAIL,
            self::SOCIAL,
            self::SHOWROOM,
        ];
    }

    public static function require_valid(string $context): string {
        $context = strtolower(trim($context));
        if (!in_array($context, array_merge(self::all(), [self::DEFAULT]), true)) {
            throw new \coding_exception('Unsupported Commerce product cover context: ' . $context);
        }
        return $context;
    }
}
