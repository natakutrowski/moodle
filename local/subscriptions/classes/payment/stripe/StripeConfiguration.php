<?php
namespace local_subscriptions\payment\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Central Stripe account/profile configuration.
 *
 * Profiles:
 * - test: Stripe test account
 * - live_ei: historical LIVE account (backward-compatible stripe_live_* keys)
 * - live_sas: second production account
 */
final class StripeConfiguration {
    public const TEST = 'test';
    public const LIVE_EI = 'live_ei';
    public const LIVE_SAS = 'live_sas';

    public const PROFILES = [self::TEST, self::LIVE_EI, self::LIVE_SAS];

    public static function active_profile(): string {
        return self::normalise((string)(get_config('local_subscriptions', 'stripe_env') ?: self::TEST));
    }

    public static function normalise(?string $profile): string {
        $profile = strtolower(trim((string)$profile));
        // Existing installations stored "live". It now means the EI account.
        if ($profile === 'live') {
            return self::LIVE_EI;
        }
        return in_array($profile, self::PROFILES, true) ? $profile : self::TEST;
    }

    public static function is_live(?string $profile = null): bool {
        return in_array(self::normalise($profile ?? self::active_profile()), [self::LIVE_EI, self::LIVE_SAS], true);
    }

    public static function config_prefix(?string $profile = null): string {
        return match (self::normalise($profile ?? self::active_profile())) {
            self::LIVE_EI => 'stripe_live', // Preserve all existing LIVE keys.
            self::LIVE_SAS => 'stripe_live_sas',
            default => 'stripe_test',
        };
    }

    public static function get(?string $profile = null): array {
        $profile = self::normalise($profile ?? self::active_profile());
        $prefix = self::config_prefix($profile);

        return [
            'profile' => $profile,
            'mode' => self::is_live($profile) ? 'live' : 'test',
            'secret_key' => (string)(get_config('local_subscriptions', $prefix . '_secret') ?: ''),
            'publishable_key' => (string)(get_config('local_subscriptions', $prefix . '_publishable') ?: ''),
            'webhook_secret' => (string)(get_config('local_subscriptions', $prefix . '_webhook_secret') ?: ''),
            'portal_configuration_id' => (string)(get_config('local_subscriptions', $prefix . '_portal_configuration_id') ?: ''),
        ];
    }

    public static function secret_key(?string $profile = null): string {
        return self::get($profile)['secret_key'];
    }

    public static function label(?string $profile = null): string {
        return match (self::normalise($profile ?? self::active_profile())) {
            self::LIVE_EI => get_string('stripe_profile_live_ei', 'local_subscriptions'),
            self::LIVE_SAS => get_string('stripe_profile_live_sas', 'local_subscriptions'),
            default => get_string('stripe_profile_test', 'local_subscriptions'),
        };
    }
}
