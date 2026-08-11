<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\contract;

defined('MOODLE_INTERNAL') || die();

/** Stable field contract for the unified CRM purchase list. */
final class CommercePurchaseListContract {
    public const DATE = 'date';
    public const REFERENCE = 'reference';
    public const CUSTOMER = 'customer';
    public const PRODUCTS = 'products';
    public const TYPE = 'type';
    public const AMOUNT = 'amount';
    public const CURRENCY = 'currency';
    public const COMMERCIAL_STATUS = 'commercialstatus';
    public const PAYMENT_STATUS = 'paymentstatus';
    public const FULFILLMENT_STATUS = 'fulfillmentstatus';
    public const PROVIDER = 'provider';
    public const SOURCE = 'source';

    public static function fields(): array {
        return [
            self::DATE,
            self::REFERENCE,
            self::CUSTOMER,
            self::PRODUCTS,
            self::TYPE,
            self::AMOUNT,
            self::CURRENCY,
            self::COMMERCIAL_STATUS,
            self::PAYMENT_STATUS,
            self::FULFILLMENT_STATUS,
            self::PROVIDER,
            self::SOURCE,
        ];
    }
}
