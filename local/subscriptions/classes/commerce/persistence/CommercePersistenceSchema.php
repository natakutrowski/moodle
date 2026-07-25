<?php

namespace local_subscriptions\commerce\persistence;

defined('MOODLE_INTERNAL') || die();

/**
 * Central contract for the native Commerce persistence model.
 *
 * This class contains names and limits only. It does not access the database
 * and deliberately precedes install.xml and upgrade.php changes.
 */
final class CommercePersistenceSchema {

    public const TABLE_PURCHASE = 'local_subscriptions_commerce_purchase';
    public const TABLE_ITEM = 'local_subscriptions_commerce_purchase_item';
    public const TABLE_PAYMENT = 'local_subscriptions_commerce_payment';
    public const TABLE_FULFILLMENT = 'local_subscriptions_commerce_fulfillment';

    public const SNAPSHOT_VERSION = 1;

    public const PURCHASE_ID_LENGTH = 32;
    public const PURCHASE_REFERENCE_LENGTH = 28;
    public const TYPE_LENGTH = 64;
    public const STATUS_LENGTH = 32;
    public const CURRENCY_LENGTH = 3;
    public const EMAIL_LENGTH = 254;
    public const PROVIDER_LENGTH = 64;
    public const PROVIDER_REFERENCE_LENGTH = 255;
    public const TRANSACTION_ID_LENGTH = 255;
    public const ITEM_REFERENCE_LENGTH = 255;
    public const ITEM_LABEL_LENGTH = 255;
    public const FULFILLMENT_REFERENCE_LENGTH = 255;
    public const FULFILLMENT_KEY_LENGTH = 100;
    public const IDEMPOTENCY_KEY_LENGTH = 255;
    public const LEGACY_FAMILY_LENGTH = 32;

    /** @return string[] */
    public static function table_names(): array {
        return [
            self::TABLE_PURCHASE,
            self::TABLE_ITEM,
            self::TABLE_PAYMENT,
            self::TABLE_FULFILLMENT,
        ];
    }

    private function __construct() {
    }
}
