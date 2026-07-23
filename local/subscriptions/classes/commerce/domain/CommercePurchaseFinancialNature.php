<?php

namespace local_subscriptions\commerce\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Financial nature of a Commerce purchase.
 *
 * This classification does not modify the historical payment status.
 * It only explains how a zero or positive amount should be interpreted.
 */
final class CommercePurchaseFinancialNature {

    public const PAID = 'paid';
    public const TRIAL = 'trial';
    public const COMPLIMENTARY = 'complimentary';
    public const FULL_DISCOUNT = 'full_discount';
    public const FREE_PRODUCT = 'free_product';
    public const ZERO_AMOUNT_UNCLASSIFIED = 'zero_amount_unclassified';

    private const VALID_VALUES = [
        self::PAID,
        self::TRIAL,
        self::COMPLIMENTARY,
        self::FULL_DISCOUNT,
        self::FREE_PRODUCT,
        self::ZERO_AMOUNT_UNCLASSIFIED,
    ];

    public static function is_valid(
        string $value
    ): bool {
        return in_array(
            $value,
            self::VALID_VALUES,
            true
        );
    }

    public static function is_legitimate_zero_amount(
        string $value
    ): bool {
        return in_array(
            $value,
            [
                self::TRIAL,
                self::COMPLIMENTARY,
                self::FULL_DISCOUNT,
                self::FREE_PRODUCT,
            ],
            true
        );
    }

    /**
     * @return string[]
     */
    public static function all(): array {
        return self::VALID_VALUES;
    }
}