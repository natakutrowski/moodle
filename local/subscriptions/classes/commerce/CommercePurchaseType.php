<?php

namespace local_subscriptions\commerce;

defined('MOODLE_INTERNAL') || die();

/**
 * Supported Commerce purchase families.
 */
final class CommercePurchaseType {

    public const SUBSCRIPTION = 'subscription';
    public const DIGITAL = 'digital';

    private const VALID_TYPES = [
        self::SUBSCRIPTION,
        self::DIGITAL,
    ];

    public static function is_valid(
        string $type
    ): bool {
        return in_array(
            strtolower(trim($type)),
            self::VALID_TYPES,
            true
        );
    }

    public static function normalise(
        string $type
    ): string {
        $type = strtolower(
            trim($type)
        );

        if (!self::is_valid($type)) {
            throw new \InvalidArgumentException(
                'Unsupported Commerce purchase type: ' . $type
            );
        }

        return $type;
    }

    /**
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_TYPES;
    }
}