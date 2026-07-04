<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminEvents {

    // Emails.
    public const EMAIL_CUSTOM_SENT = 'email.custom.sent';
    public const EMAIL_PASSWORD_RESET_NOTICE_SENT = 'email.password_reset_notice.sent';
    public const EMAIL_WELCOME_SENT = 'email.welcome.sent';

    // Users.
    public const USER_PASSWORD_UPDATED = 'user.password.updated';
    public const USER_NOTE_ADDED = 'user.note.added';

    // Subscriptions.
    public const SUBSCRIPTION_CREATED = 'subscription.created';
    public const SUBSCRIPTION_CREATED_MANUAL = 'subscription.created_manual';
    public const SUBSCRIPTION_UPDATED = 'subscription.updated';
    public const SUBSCRIPTION_DELETED = 'subscription.deleted';
    public const SUBSCRIPTION_STATUS_UPDATED = 'subscription.status_updated';
    public const SUBSCRIPTION_DATES_UPDATED = 'subscription.dates_updated';

    // Digital products.
    public const DIGITAL_PURCHASE_CREATED = 'digital.purchase.created';
    public const DIGITAL_PURCHASE_PAID = 'digital.purchase.paid';
    public const DIGITAL_PURCHASE_FAILED = 'digital.purchase.failed';
    public const DIGITAL_LINK_RESENT = 'digital.link.resent';

    public const DIGITAL_TOKEN_REGENERATED = 'digital.token.regenerated';
    public const DIGITAL_TOKEN_EXTENDED = 'digital.token.extended';
    public const DIGITAL_PROVIDER_CHECKED = 'digital.provider.checked';

    // Payments.
    public const PAYMENT_REQUEST_CREATED = 'payment_request.created';
    public const PAYMENT_REQUEST_PAID = 'payment_request.paid';
    public const PAYMENT_REQUEST_FAILED = 'payment_request.failed';
    public const PAYMENT_REQUEST_CANCELLED = 'payment_request.cancelled';

    // Trials.
    public const TRIAL_STARTED = 'trial.started';
    public const TRIAL_EXPIRED = 'trial.expired';

    public static function all(): array {
        return [
            self::EMAIL_CUSTOM_SENT,
            self::EMAIL_PASSWORD_RESET_NOTICE_SENT,
            self::EMAIL_WELCOME_SENT,

            self::USER_PASSWORD_UPDATED,
            self::USER_NOTE_ADDED,

            self::SUBSCRIPTION_CREATED,
            self::SUBSCRIPTION_CREATED_MANUAL,
            self::SUBSCRIPTION_UPDATED,
            self::SUBSCRIPTION_DELETED,
            self::SUBSCRIPTION_STATUS_UPDATED,
            self::SUBSCRIPTION_DATES_UPDATED,

            self::DIGITAL_PURCHASE_CREATED,
            self::DIGITAL_PURCHASE_PAID,
            self::DIGITAL_PURCHASE_FAILED,
            self::DIGITAL_LINK_RESENT,
            self::DIGITAL_TOKEN_REGENERATED,
            self::DIGITAL_TOKEN_EXTENDED,
            self::DIGITAL_PROVIDER_CHECKED,

            self::PAYMENT_REQUEST_CREATED,
            self::PAYMENT_REQUEST_PAID,
            self::PAYMENT_REQUEST_FAILED,
            self::PAYMENT_REQUEST_CANCELLED,

            self::TRIAL_STARTED,
            self::TRIAL_EXPIRED,
        ];
    }

    public static function exists(string $event): bool {
        return in_array($event, self::all(), true);
    }
}