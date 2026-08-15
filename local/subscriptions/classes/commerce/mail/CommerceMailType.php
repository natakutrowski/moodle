<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Supported transactional Commerce mail intentions.
 */
final class CommerceMailType {

    public const PURCHASE_ACCESS = 'purchase_access';
    public const GRANT_ACCESS = 'grant_access';
    public const PURCHASE_RECEIPT = 'purchase_receipt';
    public const PAYMENT_FAILED = 'payment_failed';
    public const PAYMENT_PENDING = 'payment_pending';
    public const PAYMENT_CANCELLED = 'payment_cancelled';
    public const ACCOUNT_ACTIVATION = 'account_activation';
    public const PERSONAL_OFFER = 'personal_offer';
    public const TRIAL_WELCOME = 'trial_welcome';
    public const MARKETING_CAMPAIGN = 'marketing_campaign';
    public const SALES_FOLLOWUP = 'sales_followup';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::PURCHASE_ACCESS,
            self::GRANT_ACCESS,
            self::PURCHASE_RECEIPT,
            self::PAYMENT_FAILED,
            self::PAYMENT_PENDING,
            self::PAYMENT_CANCELLED,
            self::ACCOUNT_ACTIVATION,
            self::PERSONAL_OFFER,
            self::TRIAL_WELCOME,
        ];
    }

    /**
     * Every type accepted by the persistent outbox. Generic Mail Studio
     * campaigns are routable, but do not belong to the legacy transactional
     * template-default catalogue returned by all().
     *
     * @return string[]
     */
    public static function routable(): array {
        return array_merge(self::all(), [self::MARKETING_CAMPAIGN, self::SALES_FOLLOWUP]);
    }

    public static function normalise(string $type): string {
        $type = strtolower(trim($type));

        if (!in_array($type, self::routable(), true)) {
            throw new \coding_exception(
                'Unsupported Commerce transactional mail type: ' . $type
            );
        }

        return $type;
    }

    private function __construct() {
    }
}
