<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseType;

/**
 * Origin of a Commerce purchase.
 */
final class CommercePurchaseSource {

    public const LEGACY_SUBSCRIPTION =
        'legacy_subscription';

    public const LEGACY_DIGITAL =
        'legacy_digital';

    public const CHECKOUT =
        'checkout';

    public const ADMIN =
        'admin';

    public const IMPORT =
        'import';

    public const API =
        'api';

    public const UNKNOWN =
        'unknown';

    /**
     * Supported purchase sources.
     */
    private const VALID_SOURCES = [
        self::LEGACY_SUBSCRIPTION,
        self::LEGACY_DIGITAL,
        self::CHECKOUT,
        self::ADMIN,
        self::IMPORT,
        self::API,
        self::UNKNOWN,
    ];

    /**
     * Determine the source of an existing Commerce purchase.
     *
     * Explicit metadata is preferred when available. Otherwise the source is
     * derived from the Commerce purchase type.
     *
     * @param CommercePurchase $purchase Commerce purchase.
     * @return string
     */
    public static function from_purchase(
        CommercePurchase $purchase
    ): string {
        $configuredsource =
            $purchase->get_metadata_value(
                'commerce_source'
            );

        if (
            is_string($configuredsource)
            && self::is_valid($configuredsource)
        ) {
            return self::normalise(
                $configuredsource
            );
        }

        return match ($purchase->get_type()) {
            CommercePurchaseType::SUBSCRIPTION =>
                self::LEGACY_SUBSCRIPTION,

            CommercePurchaseType::DIGITAL =>
                self::LEGACY_DIGITAL,

            default =>
                self::UNKNOWN,
        };
    }

    /**
     * Whether a source is supported.
     *
     * @param string $source Source.
     * @return bool
     */
    public static function is_valid(
        string $source
    ): bool {
        return in_array(
            strtolower(
                trim($source)
            ),
            self::VALID_SOURCES,
            true
        );
    }

    /**
     * Normalise a Commerce purchase source.
     *
     * @param string $source Source.
     * @return string
     */
    public static function normalise(
        string $source
    ): string {
        $source =
            strtolower(
                trim($source)
            );

        if (!self::is_valid($source)) {
            throw new \InvalidArgumentException(
                'Unsupported Commerce purchase source: '
                    . $source
            );
        }

        return $source;
    }

    /**
     * Return all supported sources.
     *
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_SOURCES;
    }
}