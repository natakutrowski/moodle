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

    // Native Commerce purchases.
    public const COMMERCE_PURCHASE_FULFILLMENT_RETRIED =
        'commerce.purchase.fulfillment_retried';

    public const COMMERCE_PURCHASE_NOTE_ADDED =
        'commerce.purchase.note_added';

    public const COMMERCE_PURCHASE_FULFILLMENT_CLOSED_WITHOUT_DELIVERY =
        'commerce.purchase.fulfillment_closed_without_delivery';

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

    public const WORK_ITEM_CREATED =
        'work_item.created';

    public const WORK_ITEM_STATUS_CHANGED =
        'work_item.status_changed';

    public const WORK_ITEM_PRIORITY_CHANGED =
        'work_item.priority_changed';

    public const WORK_ITEM_ASSIGNED =
        'work_item.assigned';

    public const WORK_ITEM_COMMENT_ADDED =
        'work_item.comment_added';

    public const WORK_ITEM_LINKED =
        'work_item.linked';  
        
    public const WORK_ITEM_SUGGESTION_OPENED =
        'work_item.suggestion_opened';

    public const WORK_ITEM_CREATED_FROM_RECOMMENDATION =
        'work_item.created_from_recommendation';

    public const WORK_ITEM_DUPLICATE_OVERRIDE =
        'work_item.duplicate_override';

    public const RECOMMENDATION_CREATED =
        'recommendation.created';

    public const RECOMMENDATION_REFRESHED =
        'recommendation.refreshed';

    public const RECOMMENDATION_ACCEPTED =
        'recommendation.accepted';

    public const RECOMMENDATION_DISMISSED =
        'recommendation.dismissed';

    public const RECOMMENDATION_COMPLETED =
        'recommendation.completed';

    public const RECOMMENDATION_EXPIRED =
        'recommendation.expired';   

    public const RECOMMENDATION_RUN_COMPLETED =
        'recommendation.run_completed';

    public const RECOMMENDATION_RUN_PARTIAL =
        'recommendation.run_partial';

    public const RECOMMENDATION_RUN_FAILED =
        'recommendation.run_failed';

    public const RECOMMENDATION_RUN_SKIPPED =
        'recommendation.run_skipped';

    // Customer Success plans.
    public const CUSTOMER_SUCCESS_PLAN_CREATED =
        'customer_success.plan.created';

    public const CUSTOMER_SUCCESS_PLAN_ACTIVATED =
        'customer_success.plan.activated';

    public const CUSTOMER_SUCCESS_PLAN_PAUSED =
        'customer_success.plan.paused';

    public const CUSTOMER_SUCCESS_PLAN_CANCELLED =
        'customer_success.plan.cancelled';

    public const CUSTOMER_SUCCESS_PLAN_COMPLETED =
        'customer_success.plan.completed';

    public const CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED =
        'customer_success.plan.auto_completed';

    public const CUSTOMER_SUCCESS_STEP_STARTED =
        'customer_success.step.started';

    public const CUSTOMER_SUCCESS_STEP_COMPLETED =
        'customer_success.step.completed';

    public const CUSTOMER_SUCCESS_STEP_SKIPPED =
        'customer_success.step.skipped';

    public const CUSTOMER_SUCCESS_STEP_BLOCKED =
        'customer_success.step.blocked';

    public const CUSTOMER_SUCCESS_STEP_UNBLOCKED =
        'customer_success.step.unblocked';        

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

            self::COMMERCE_PURCHASE_FULFILLMENT_RETRIED,
            self::COMMERCE_PURCHASE_FULFILLMENT_CLOSED_WITHOUT_DELIVERY,
            self::COMMERCE_PURCHASE_NOTE_ADDED,

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

            self::WORK_ITEM_CREATED,
            self::WORK_ITEM_STATUS_CHANGED,
            self::WORK_ITEM_PRIORITY_CHANGED,
            self::WORK_ITEM_ASSIGNED,
            self::WORK_ITEM_COMMENT_ADDED,
            self::WORK_ITEM_LINKED,
            self::WORK_ITEM_SUGGESTION_OPENED,
            self::WORK_ITEM_CREATED_FROM_RECOMMENDATION,
            self::WORK_ITEM_DUPLICATE_OVERRIDE,

            self::RECOMMENDATION_CREATED,
            self::RECOMMENDATION_REFRESHED,
            self::RECOMMENDATION_ACCEPTED,
            self::RECOMMENDATION_DISMISSED,
            self::RECOMMENDATION_COMPLETED,
            self::RECOMMENDATION_EXPIRED,
            self::RECOMMENDATION_RUN_COMPLETED,
            self::RECOMMENDATION_RUN_PARTIAL,
            self::RECOMMENDATION_RUN_FAILED,
            self::RECOMMENDATION_RUN_SKIPPED,

            self::CUSTOMER_SUCCESS_PLAN_CREATED,
            self::CUSTOMER_SUCCESS_PLAN_ACTIVATED,
            self::CUSTOMER_SUCCESS_PLAN_PAUSED,
            self::CUSTOMER_SUCCESS_PLAN_CANCELLED,
            self::CUSTOMER_SUCCESS_PLAN_COMPLETED,
            self::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED,

            self::CUSTOMER_SUCCESS_STEP_STARTED,
            self::CUSTOMER_SUCCESS_STEP_COMPLETED,
            self::CUSTOMER_SUCCESS_STEP_SKIPPED,
            self::CUSTOMER_SUCCESS_STEP_BLOCKED,
            self::CUSTOMER_SUCCESS_STEP_UNBLOCKED,
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