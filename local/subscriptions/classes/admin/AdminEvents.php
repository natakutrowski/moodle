<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminEvents {

    // Emails.
    public const EMAIL_CUSTOM_SENT =
        'email.custom.sent';

    public const EMAIL_PASSWORD_RESET_NOTICE_SENT =
        'email.password_reset_notice.sent';

    public const EMAIL_WELCOME_SENT =
        'email.welcome.sent';

    public const EMAIL_RECEIPT_SENT =
        'email.receipt.sent';

    public const EMAIL_SUBSCRIPTION_ACCESS_SENT =
        'email.subscription_access.sent';

    // Users.
    public const USER_PASSWORD_UPDATED =
        'user.password.updated';

    public const USER_NOTE_ADDED =
        'user.note.added';

    public const USER_SUSPENDED =
        'user.suspended';

    public const USER_REACTIVATED =
        'user.reactivated';

    // Subscriptions.
    public const SUBSCRIPTION_CREATED =
        'subscription.created';

    public const SUBSCRIPTION_CREATED_MANUAL =
        'subscription.created_manual';

    public const SUBSCRIPTION_UPDATED =
        'subscription.updated';

    public const SUBSCRIPTION_DELETED =
        'subscription.deleted';

    public const SUBSCRIPTION_STATUS_UPDATED =
        'subscription.status_updated';

    public const SUBSCRIPTION_DATES_UPDATED =
        'subscription.dates_updated';

    public const SUBSCRIPTION_CREATED_AUTO =
        'subscription.created_auto';

    public const SUBSCRIPTION_EXTENDED =
        'subscription.extended';

    // Digital products.
    public const DIGITAL_PURCHASE_CREATED =
        'digital.purchase.created';

    public const DIGITAL_PURCHASE_PAID =
        'digital.purchase.paid';

    public const DIGITAL_PURCHASE_FAILED =
        'digital.purchase.failed';

    public const DIGITAL_PURCHASE_CANCELLED =
        'digital.purchase.cancelled';

    public const DIGITAL_LINK_RESENT =
        'digital.link.resent';

    public const DIGITAL_TOKEN_REGENERATED =
        'digital.token.regenerated';

    public const DIGITAL_TOKEN_EXTENDED =
        'digital.token.extended';

    public const DIGITAL_PROVIDER_CHECKED =
        'digital.provider.checked';

    // Payments.
    public const PAYMENT_REQUEST_CREATED =
        'payment_request.created';

    public const PAYMENT_REQUEST_PAID =
        'payment_request.paid';

    public const PAYMENT_REQUEST_FAILED =
        'payment_request.failed';

    public const PAYMENT_REQUEST_CANCELLED =
        'payment_request.cancelled';

    // Trials.
    public const TRIAL_STARTED =
        'trial.started';

    public const TRIAL_EXPIRED =
        'trial.expired';

    // CRM Inbox.
    public const INBOX_MESSAGE_RECEIVED =
        'inbox.message.received';

    public const INBOX_REPLY_SENT =
        'inbox.reply.sent';

    public const INBOX_THREAD_ASSIGNED =
        'inbox.thread.assigned';

    public const INBOX_THREAD_UNASSIGNED =
        'inbox.thread.unassigned';

    public const INBOX_THREAD_STATUS_CHANGED =
        'inbox.thread.status_changed';

    public const INBOX_THREAD_PRIORITY_CHANGED =
        'inbox.thread.priority_changed';

    public const INBOX_AI_ANALYSIS_EXECUTED =
        'inbox.ai.analysis_executed';

    public const INBOX_AI_REPLY_SUGGESTED =
        'inbox.ai.reply_suggested';

    public static function all(): array {
        return [
            self::EMAIL_CUSTOM_SENT,
            self::EMAIL_PASSWORD_RESET_NOTICE_SENT,
            self::EMAIL_WELCOME_SENT,
            self::EMAIL_RECEIPT_SENT,
            self::EMAIL_SUBSCRIPTION_ACCESS_SENT,

            self::USER_PASSWORD_UPDATED,
            self::USER_NOTE_ADDED,
            self::USER_SUSPENDED,
            self::USER_REACTIVATED,

            self::SUBSCRIPTION_CREATED,
            self::SUBSCRIPTION_CREATED_MANUAL,
            self::SUBSCRIPTION_UPDATED,
            self::SUBSCRIPTION_DELETED,
            self::SUBSCRIPTION_STATUS_UPDATED,
            self::SUBSCRIPTION_DATES_UPDATED,
            self::SUBSCRIPTION_CREATED_AUTO,
            self::SUBSCRIPTION_EXTENDED,

            self::DIGITAL_PURCHASE_CREATED,
            self::DIGITAL_PURCHASE_PAID,
            self::DIGITAL_PURCHASE_FAILED,
            self::DIGITAL_PURCHASE_CANCELLED,
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

            self::INBOX_MESSAGE_RECEIVED,
            self::INBOX_REPLY_SENT,
            self::INBOX_THREAD_ASSIGNED,
            self::INBOX_THREAD_UNASSIGNED,
            self::INBOX_THREAD_STATUS_CHANGED,
            self::INBOX_THREAD_PRIORITY_CHANGED,
            self::INBOX_AI_ANALYSIS_EXECUTED,
            self::INBOX_AI_REPLY_SUGGESTED,
        ];
    }

    public static function exists(
        string $event
    ): bool {
        return in_array(
            $event,
            self::all(),
            true
        );
    }
}