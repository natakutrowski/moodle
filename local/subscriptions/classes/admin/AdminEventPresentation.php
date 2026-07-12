<?php

namespace local_subscriptions\admin;

defined('MOODLE_INTERNAL') || die();

final class AdminEventPresentation {

    private const ICONS = [
        AdminEvents::EMAIL_CUSTOM_SENT => '✉️',

        AdminEvents::DIGITAL_PURCHASE_CREATED => '📦',
        AdminEvents::DIGITAL_PURCHASE_PAID => '💳',
        AdminEvents::DIGITAL_PURCHASE_FAILED => '⚠️',
        AdminEvents::DIGITAL_PURCHASE_CANCELLED => '🚫',
        AdminEvents::DIGITAL_LINK_RESENT => '✉️',
        AdminEvents::DIGITAL_TOKEN_REGENERATED => '🔗',
        AdminEvents::DIGITAL_TOKEN_EXTENDED => '🕒',

        AdminEvents::USER_SUSPENDED => '⏸️',
        AdminEvents::USER_REACTIVATED => '▶️',
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
    ];

    public static function label(string $event): string {
        $stringkey = self::STRING_KEYS[$event] ?? null;

        if ($stringkey !== null) {
            return get_string(
                $stringkey,
                'local_subscriptions'
            );
        }

        return self::fallback_label($event);
    }

    public static function icon(string $event): string {
        return self::ICONS[$event] ?? '⚙️';
    }

    public static function category(string $event): string {
        if (str_starts_with($event, 'digital.')) {
            return 'digital';
        }

        if (str_starts_with($event, 'email.')) {
            return 'email';
        }

        if (str_starts_with($event, 'user.')) {
            return 'user';
        }

        if (str_starts_with($event, 'subscription.')) {
            return 'subscription';
        }

        if (str_starts_with($event, 'automation.')) {
            return 'automation';
        }

        return 'system';
    }

    private static function fallback_label(string $event): string {
        $label = str_replace(
            ['.', '_', '-'],
            ' ',
            trim($event)
        );

        $label = preg_replace('/\s+/', ' ', $label);

        if ($label === null || $label === '') {
            return get_string(
                'admin_event_unknown',
                'local_subscriptions'
            );
        }

        return ucfirst($label);
    }
}