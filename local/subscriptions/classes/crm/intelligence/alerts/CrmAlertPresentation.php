<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

/**
 * Central presentation layer for CRM alerts.
 *
 * Technical alert keys remain stable inside the CRM domain.
 * Human-readable labels and visual metadata are resolved here.
 *
 * This class must remain independent from repositories and renderers.
 */
final class CrmAlertPresentation {

    /**
     * Returns all supported CRM alert keys.
     *
     * @return string[]
     */
    public static function keys(): array {
        return [
            'high_risk_user',
            'trial_without_purchase',
            'expired_without_reactivation',
            'inactive_user',
            'hot_opportunity',
        ];
    }

    /**
     * Returns whether an alert key is officially supported.
     */
    public static function is_known(
        string $alertkey
    ): bool {
        return in_array(
            $alertkey,
            self::keys(),
            true
        );
    }

    /**
     * Returns the translated business label for an alert.
     */
    public static function label(
        string $alertkey
    ): string {
        $alertkey = self::normalize_key(
            $alertkey
        );

        if (!self::is_known($alertkey)) {
            return get_string(
                'crm_intelligence_alert_fallback',
                'local_subscriptions'
            );
        }

        return get_string(
            'crm_intelligence_alert_' . $alertkey,
            'local_subscriptions'
        );
    }

    /**
     * Returns the normalized severity for an alert.
     *
     * The alert-provided severity remains authoritative when valid.
     */
    public static function severity(
        string $alertkey,
        ?string $severity = null
    ): string {
        if (
            $severity !== null &&
            in_array(
                $severity,
                self::severity_keys(),
                true
            )
        ) {
            return $severity;
        }

        return match (
            self::normalize_key($alertkey)
        ) {
            'high_risk_user' =>
                'danger',

            'trial_without_purchase',
            'expired_without_reactivation',
            'inactive_user' =>
                'warning',

            'hot_opportunity' =>
                'success',

            default =>
                'info',
        };
    }

    /**
     * Returns the Bootstrap border class for an alert.
     */
    public static function border_class(
        string $alertkey,
        ?string $severity = null
    ): string {
        return match (
            self::severity(
                $alertkey,
                $severity
            )
        ) {
            'danger' =>
                'border-danger',

            'warning' =>
                'border-warning',

            'success' =>
                'border-success',

            default =>
                'border-info',
        };
    }

    /**
     * Returns the Bootstrap text class for an alert.
     */
    public static function text_class(
        string $alertkey,
        ?string $severity = null
    ): string {
        return match (
            self::severity(
                $alertkey,
                $severity
            )
        ) {
            'danger' =>
                'text-danger',

            'warning' =>
                'text-warning',

            'success' =>
                'text-success',

            default =>
                'text-info',
        };
    }

    /**
     * Returns the visual icon associated with an alert.
     */
    public static function icon(
        string $alertkey
    ): string {
        return match (
            self::normalize_key($alertkey)
        ) {
            'high_risk_user' =>
                '⚠️',

            'trial_without_purchase' =>
                '🧪',

            'expired_without_reactivation' =>
                '⌛',

            'inactive_user' =>
                '💤',

            'hot_opportunity' =>
                '🔥',

            default =>
                '🚨',
        };
    }

    /**
     * Returns a stable numeric priority for an alert key.
     */
    public static function default_priority(
        string $alertkey
    ): int {
        return match (
            self::normalize_key($alertkey)
        ) {
            'high_risk_user' =>
                95,

            'trial_without_purchase' =>
                85,

            'expired_without_reactivation' =>
                80,

            'hot_opportunity' =>
                75,

            'inactive_user' =>
                70,

            default =>
                50,
        };
    }

    /**
     * Returns the normalized business priority level.
     */
    public static function priority_level(
        int $priority
    ): string {
        if ($priority >= 90) {
            return 'critical';
        }

        if ($priority >= 75) {
            return 'high';
        }

        return 'normal';
    }

    /**
     * Returns the translated business priority label.
     */
    public static function priority_label(
        int $priority
    ): string {
        return get_string(
            'crm_intelligence_alert_priority_' .
                self::priority_level($priority),
            'local_subscriptions'
        );
    }

    /**
     * Returns the CSS badge class associated with a business priority.
     */
    public static function priority_badge_class(
        int $priority
    ): string {
        return match (
            self::priority_level($priority)
        ) {
            'critical' =>
                'bg-danger',

            'high' =>
                'bg-warning text-dark',

            default =>
                'bg-secondary',
        };
    }

    /**
     * Returns the translated recommended next action.
     */
    public static function next_action_label(
        string $alertkey
    ): string {
        $alertkey = self::normalize_key(
            $alertkey
        );

        if (!self::is_known($alertkey)) {
            return get_string(
                'crm_intelligence_alert_next_action_default',
                'local_subscriptions'
            );
        }

        return get_string(
            'crm_intelligence_alert_next_action_' .
                $alertkey,
            'local_subscriptions'
        );
    }

    /**
     * Returns whether a persisted CRM signal time is usable.
     */
    public static function has_signal_time(
        ?int $timestamp
    ): bool {
        return
            $timestamp !== null &&
            $timestamp > 0;
    }

    /**
     * Returns the translated exact signal date.
     */
    public static function signal_date_label(
        ?int $timestamp
    ): ?string {
        if (!self::has_signal_time($timestamp)) {
            return null;
        }

        return get_string(
            'crm_intelligence_alert_signal_date',
            'local_subscriptions',
            userdate(
                $timestamp,
                get_string(
                    'strftimedatetimeshort',
                    'langconfig'
                )
            )
        );
    }

    /**
     * Returns the translated signal age.
     */
    public static function signal_age_label(
        ?int $timestamp,
        ?int $now = null
    ): ?string {
        if (!self::has_signal_time($timestamp)) {
            return null;
        }

        $now ??= time();

        $duration = max(
            0,
            $now - $timestamp
        );

        return get_string(
            'crm_intelligence_alert_signal_age',
            'local_subscriptions',
            format_time($duration)
        );
    }

    /**
     * Returns the normalized supported severity keys.
     *
     * @return string[]
     */
    private static function severity_keys(): array {
        return [
            'danger',
            'warning',
            'success',
            'info',
        ];
    }

    /**
     * Normalizes an external alert key without exposing raw values.
     */
    private static function normalize_key(
        string $alertkey
    ): string {
        return clean_param(
            trim($alertkey),
            PARAM_ALPHANUMEXT
        );
    }
}