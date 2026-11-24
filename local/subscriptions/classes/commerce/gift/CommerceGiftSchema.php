<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** G2B additive schema: no gift issuance or redemption is connected. */
final class CommerceGiftSchema {
    public const VERSION = 2026112201;
    public const TABLE_GIFT = 'local_subs_commerce_gift';
    public const TABLE_TOKEN = 'local_subs_commerce_gift_token';
    public const TABLE_EVENT = 'local_subs_commerce_gift_event';

    /** DB uniqueness prevents duplicate issuance and replayed invitation/event inserts. */
    public const REQUIRED_UNIQUE_INDEXES = [
        self::TABLE_GIFT => [
            'giftuuid_uix' => ['giftuuid'],
            'issuekey_uix' => ['issuekey'],
        ],
        self::TABLE_TOKEN => [
            'tokenhash_uix' => ['tokenhash'],
        ],
        self::TABLE_EVENT => [
            'event_idem_uix' => ['idempotencykey'],
        ],
    ];

    public const REQUIRED_FIELDS = [
        self::TABLE_GIFT => ['id', 'giftuuid', 'issuekey', 'purchaseid', 'purchaseitemid',
            'unitindex', 'buyeruserid', 'buyeremail', 'sourceproductsku', 'sourcepriceid',
            'sourcetype', 'rightssnapshotjson', 'purchasesnapshotjson', 'deliverymode',
            'intendedrecipientuserid', 'intendedrecipientemail', 'rightsholderuserid',
            'status', 'scheduledat', 'expiresat', 'claimedat', 'activatedat', 'timecreated',
            'timemodified', 'rowversion'],
        self::TABLE_TOKEN => ['id', 'giftid', 'tokenhash', 'issuedat', 'expiresat', 'revokedat', 'usedat'],
        self::TABLE_EVENT => ['id', 'giftid', 'eventtype', 'actoruserid', 'occurredat',
            'idempotencykey', 'detailjson'],
    ];
}
