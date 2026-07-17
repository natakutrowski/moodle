<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminEventPresentation {

    private const ICONS = [
        AdminEvents::EMAIL_CUSTOM_SENT =>
            '✉️',

        AdminEvents::DIGITAL_PURCHASE_CREATED =>
            '📦',

        AdminEvents::DIGITAL_PURCHASE_PAID =>
            '💳',

        AdminEvents::DIGITAL_PURCHASE_FAILED =>
            '⚠️',

        AdminEvents::DIGITAL_PURCHASE_CANCELLED =>
            '🚫',

        AdminEvents::DIGITAL_LINK_RESENT =>
            '✉️',

        AdminEvents::DIGITAL_TOKEN_REGENERATED =>
            '🔗',

        AdminEvents::DIGITAL_TOKEN_EXTENDED =>
            '🕒',

        AdminEvents::USER_SUSPENDED =>
            '⏸️',

        AdminEvents::USER_REACTIVATED =>
            '▶️',

        // CRM Inbox.
        AdminEvents::INBOX_MESSAGE_RECEIVED =>
            '📥',

        AdminEvents::INBOX_REPLY_SENT =>
            '📤',

        AdminEvents::INBOX_THREAD_ASSIGNED =>
            '👤',

        AdminEvents::INBOX_THREAD_UNASSIGNED =>
            '👥',

        AdminEvents::INBOX_THREAD_STATUS_CHANGED =>
            '🔄',

        AdminEvents::INBOX_THREAD_PRIORITY_CHANGED =>
            '🚩',

        AdminEvents::INBOX_AI_ANALYSIS_EXECUTED =>
            '✨',

        AdminEvents::INBOX_AI_REPLY_SUGGESTED =>
            '💡',

        // Customer Success.
        AdminEvents::CUSTOMER_SUCCESS_PLAN_CREATED =>
            '🧭',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_ACTIVATED =>
            '▶️',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_PAUSED =>
            '⏸️',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_CANCELLED =>
            '🚫',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_COMPLETED =>
            '✅',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED =>
            '🎯',

        AdminEvents::CUSTOMER_SUCCESS_STEP_STARTED =>
            '🚀',

        AdminEvents::CUSTOMER_SUCCESS_STEP_COMPLETED =>
            '✔️',

        AdminEvents::CUSTOMER_SUCCESS_STEP_SKIPPED =>
            '⏭️',

        AdminEvents::CUSTOMER_SUCCESS_STEP_BLOCKED =>
            '⛔',

        AdminEvents::CUSTOMER_SUCCESS_STEP_UNBLOCKED =>
            '🔓',

    ];

    private const STRING_KEYS = [
        AdminEvents::EMAIL_CUSTOM_SENT =>
            'admin_event_email_custom_sent',

        AdminEvents::DIGITAL_PURCHASE_CREATED =>
            'admin_event_digital_purchase_created',

        AdminEvents::DIGITAL_PURCHASE_PAID =>
            'admin_event_digital_purchase_paid',

        AdminEvents::DIGITAL_PURCHASE_FAILED =>
            'admin_event_digital_purchase_failed',

        AdminEvents::DIGITAL_PURCHASE_CANCELLED =>
            'admin_event_digital_purchase_cancelled',

        AdminEvents::DIGITAL_LINK_RESENT =>
            'admin_event_digital_link_resent',

        AdminEvents::DIGITAL_TOKEN_REGENERATED =>
            'admin_event_digital_token_regenerated',

        AdminEvents::DIGITAL_TOKEN_EXTENDED =>
            'admin_event_digital_token_extended',

        AdminEvents::USER_SUSPENDED =>
            'admin_event_user_suspended',

        AdminEvents::USER_REACTIVATED =>
            'admin_event_user_reactivated',

        // CRM Inbox.
        AdminEvents::INBOX_MESSAGE_RECEIVED =>
            'admin_event_inbox_message_received',

        AdminEvents::INBOX_REPLY_SENT =>
            'admin_event_inbox_reply_sent',

        AdminEvents::INBOX_THREAD_ASSIGNED =>
            'admin_event_inbox_thread_assigned',

        AdminEvents::INBOX_THREAD_UNASSIGNED =>
            'admin_event_inbox_thread_unassigned',

        AdminEvents::INBOX_THREAD_STATUS_CHANGED =>
            'admin_event_inbox_thread_status_changed',

        AdminEvents::INBOX_THREAD_PRIORITY_CHANGED =>
            'admin_event_inbox_thread_priority_changed',

        AdminEvents::INBOX_AI_ANALYSIS_EXECUTED =>
            'admin_event_inbox_ai_analysis_executed',

        AdminEvents::INBOX_AI_REPLY_SUGGESTED =>
            'admin_event_inbox_ai_reply_suggested',

        // Customer Success.
        AdminEvents::CUSTOMER_SUCCESS_PLAN_CREATED =>
            'admin_event_customer_success_plan_created',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_ACTIVATED =>
            'admin_event_customer_success_plan_activated',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_PAUSED =>
            'admin_event_customer_success_plan_paused',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_CANCELLED =>
            'admin_event_customer_success_plan_cancelled',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_COMPLETED =>
            'admin_event_customer_success_plan_completed',

        AdminEvents::CUSTOMER_SUCCESS_PLAN_AUTO_COMPLETED =>
            'admin_event_customer_success_plan_auto_completed',

        AdminEvents::CUSTOMER_SUCCESS_STEP_STARTED =>
            'admin_event_customer_success_step_started',

        AdminEvents::CUSTOMER_SUCCESS_STEP_COMPLETED =>
            'admin_event_customer_success_step_completed',

        AdminEvents::CUSTOMER_SUCCESS_STEP_SKIPPED =>
            'admin_event_customer_success_step_skipped',

        AdminEvents::CUSTOMER_SUCCESS_STEP_BLOCKED =>
            'admin_event_customer_success_step_blocked',

        AdminEvents::CUSTOMER_SUCCESS_STEP_UNBLOCKED =>
            'admin_event_customer_success_step_unblocked',

    ];

    public static function label(
        string $event
    ): string {
        $stringkey =
            self::STRING_KEYS[$event] ?? null;

        if ($stringkey !== null) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_label($event);
    }

    public static function icon(
        string $event
    ): string {
        return self::ICONS[$event] ?? '⚙️';
    }

    public static function category(
        string $event
    ): string {

        if (
            str_starts_with(
                $event,
                'customer_success.'
            )
        ) {
            return 'customer_success';
        }

        if (
            str_starts_with(
                $event,
                'inbox.'
            )
        ) {
            return 'inbox';
        }

        if (
            str_starts_with(
                $event,
                'digital.'
            )
        ) {
            return 'digital';
        }

        if (
            str_starts_with(
                $event,
                'email.'
            )
        ) {
            return 'email';
        }

        if (
            str_starts_with(
                $event,
                'user.'
            )
        ) {
            return 'user';
        }

        if (
            str_starts_with(
                $event,
                'subscription.'
            )
        ) {
            return 'subscription';
        }

        if (
            str_starts_with(
                $event,
                'automation.'
            )
        ) {
            return 'automation';
        }

        return 'system';
    }

    private static function fallback_label(
        string $event
    ): string {
        $label = str_replace(
            ['.', '_', '-'],
            ' ',
            trim($event)
        );

        $label = preg_replace(
            '/\s+/',
            ' ',
            $label
        );

        if (
            $label === null ||
            $label === ''
        ) {
            return get_string(
                'admin_event_unknown',
                'local_subscriptions'
            );
        }

        return ucfirst($label);
    }
}