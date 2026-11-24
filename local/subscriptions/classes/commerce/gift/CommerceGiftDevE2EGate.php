<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** DEV-only harness, hard-denied on PROD regardless of DB settings. */
final class CommerceGiftDevE2EGate {
    public const ENABLE_SETTING = 'commerce_gifts_dev_e2e_enabled';
    public const BUYER_SETTING = 'commerce_gifts_dev_buyerid';
    public const RECIPIENT_SETTING = 'commerce_gifts_dev_recipientid';
    public const URL_SETTING = 'commerce_gifts_dev_wwwroot';

    public static function is_dev_environment(): bool {
        global $CFG;
        return isset($CFG->dirroot) && realpath((string)$CFG->dirroot) === '/var/www/moodle-dev';
    }

    public static function is_armed(): bool {
        global $CFG;
        if (!self::is_dev_environment() || !isset($CFG->wwwroot)) {
            return false;
        }
        // Stripe TEST must be the active configured profile, never LIVE.
        $stripe = \local_subscriptions\payment\stripe\StripeConfiguration::get();
        if ($stripe['profile'] !== 'test'
            || !str_starts_with($stripe['secret_key'], 'sk_test_')
            || !str_starts_with($stripe['publishable_key'], 'pk_test_')) {
            return false;
        }
        return (string)get_config('local_subscriptions', self::ENABLE_SETTING) === '1'
            && hash_equals((string)get_config('local_subscriptions', self::URL_SETTING), (string)$CFG->wwwroot)
            && (int)get_config('local_subscriptions', self::BUYER_SETTING) > 2
            && (int)get_config('local_subscriptions', self::RECIPIENT_SETTING) > 2;
    }

    public static function require_armed(): void {
        if (!self::is_armed()) {
            throw new \DomainException('DEV gift E2E is disabled.');
        }
    }

    public static function assert_test_account(int $userid, string $role): void {
        self::require_armed();
        $key = match ($role) {
            'buyer' => self::BUYER_SETTING,
            'recipient' => self::RECIPIENT_SETTING,
            default => throw new \InvalidArgumentException('Invalid gift E2E role.'),
        };
        if ($userid <= 2 || (int)get_config('local_subscriptions', $key) !== $userid
            || is_siteadmin($userid)) {
            throw new \DomainException('This account is not authorised for DEV gift E2E.');
        }
    }
}
