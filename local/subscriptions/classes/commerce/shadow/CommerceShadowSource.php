<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Canonical runtime sources that can trigger Commerce fulfillment. */
final class CommerceShadowSource {
    public const STRIPE_WEBHOOK = 'stripe_webhook';
    public const STRIPE_RETURN = 'stripe_return';
    public const ALFA_WEBHOOK = 'alfa_webhook';
    public const ALFA_RETURN = 'alfa_return';
    public const DIGITAL_SUCCESS = 'digital_success';
    public const CHECKOUT_SUBSCRIPTION = 'checkout_subscription';
    public const CHECKOUT_DIGITAL = 'checkout_digital';
    public const CRM_MANUAL = 'crm_manual';
    public const REPAIR_JOB = 'repair_job';
    public const RECONCILIATION_JOB = 'reconciliation_job';

    private const ALL = [
        self::STRIPE_WEBHOOK,
        self::STRIPE_RETURN,
        self::ALFA_WEBHOOK,
        self::ALFA_RETURN,
        self::DIGITAL_SUCCESS,
        self::CHECKOUT_SUBSCRIPTION,
        self::CHECKOUT_DIGITAL,
        self::CRM_MANUAL,
        self::REPAIR_JOB,
        self::RECONCILIATION_JOB,
    ];

    public static function is_valid(string $source): bool {
        return in_array(strtolower(trim($source)), self::ALL, true);
    }

    public static function all(): array {
        return self::ALL;
    }
}
