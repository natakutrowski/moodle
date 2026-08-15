<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\legacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Safety gate for historical automatic mail paths that predate the unified Mail Engine.
 *
 * N5.7 deliberately makes every legacy automatic family opt-in. Operational state
 * transitions (expiring pending requests, activating/expiring subscriptions) continue
 * to run even when their old notification emails are disabled.
 */
final class CommerceLegacyAutomaticMailPolicy {
    public const MASTER = 'legacy_auto_mail_enabled';
    public const PAYMENT_REMINDERS = 'legacy_auto_payment_reminders_enabled';
    public const EXPIRY_REMINDERS = 'legacy_auto_expiry_reminders_enabled';
    public const LIFECYCLE = 'legacy_auto_lifecycle_emails_enabled';

    public static function master_enabled(): bool {
        return self::enabled(self::MASTER, false);
    }

    public static function payment_reminders_enabled(): bool {
        return self::master_enabled() && self::enabled(self::PAYMENT_REMINDERS, false);
    }

    public static function expiry_reminders_enabled(): bool {
        return self::master_enabled() && self::enabled(self::EXPIRY_REMINDERS, false);
    }

    public static function lifecycle_emails_enabled(): bool {
        return self::master_enabled() && self::enabled(self::LIFECYCLE, false);
    }

    private static function enabled(string $key, bool $default): bool {
        $value = get_config('local_subscriptions', $key);
        if ($value === false || $value === '') {
            return $default;
        }
        return (string)$value !== '0';
    }

    private function __construct() {}
}
