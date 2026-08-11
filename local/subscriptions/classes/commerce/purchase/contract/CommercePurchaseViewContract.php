<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable sections exposed by the unified CRM purchase details page. */
final class CommercePurchaseViewContract {
    public const SUMMARY = 'summary';
    public const CUSTOMER = 'customer';
    public const ITEMS = 'items';
    public const PAYMENTS = 'payments';
    public const FULFILLMENTS = 'fulfillments';
    public const TIMELINE = 'timeline';
    public const DIAGNOSTICS = 'diagnostics';

    public static function sections(): array {
        return [
            self::SUMMARY,
            self::CUSTOMER,
            self::ITEMS,
            self::PAYMENTS,
            self::FULFILLMENTS,
            self::TIMELINE,
            self::DIAGNOSTICS,
        ];
    }
}
