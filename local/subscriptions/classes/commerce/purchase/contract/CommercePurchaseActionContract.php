<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\contract;

defined('MOODLE_INTERNAL') || die();

/** Action identifiers reserved for the unified purchase command layer. */
final class CommercePurchaseActionContract {
    public const OPEN_CUSTOMER = 'open_customer';
    public const OPEN_PRODUCT = 'open_product';
    public const OPEN_PAYMENT = 'open_payment';
    public const RETRY_FULFILLMENT = 'retry_fulfillment';
    public const REPLACE = 'replace';
    public const CANCEL = 'cancel';
    public const REFUND = 'refund';
    public const ADD_NOTE = 'add_note';

    public static function actions(): array {
        return [
            self::OPEN_CUSTOMER,
            self::OPEN_PRODUCT,
            self::OPEN_PAYMENT,
            self::RETRY_FULFILLMENT,
            self::REPLACE,
            self::CANCEL,
            self::REFUND,
            self::ADD_NOTE,
        ];
    }
}
