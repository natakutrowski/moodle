<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Commercial customer policy, site-bound and fail-closed. No account allowlist,
 * short-lived test arming or acceptance of browser-supplied prices/providers.
 * The release switch must be enabled separately AFTER a full DEV E2E audit.
 */
final class CommerceGiftCommercialRuntimeGate {
    public const SWITCH = 'commerce_gifts_commercial_runtime_enabled';
    public const SITE = 'commerce_gifts_commercial_runtime_wwwroot';
    public const AUTOQUEUE = 'commerce_gifts_commercial_autoqueue_enabled';
    public const DELIVERY = 'commerce_gifts_commercial_delivery_enabled';

    public static function enabled(): bool {
        global $CFG;
        if (!CommerceGiftFeatureToggle::RUNTIME_WIRED
            || (string)get_config('local_subscriptions', self::SWITCH) !== '1'
            || !isset($CFG->wwwroot) || !str_starts_with((string)$CFG->wwwroot, 'https://')
            || !hash_equals(rtrim((string)get_config('local_subscriptions', self::SITE), '/'),
                rtrim((string)$CFG->wwwroot, '/'))
            || CommerceGiftDevE2EGate::is_armed()
            || CommerceGiftCommercialDevGate::is_armed()
            || preg_match('/\A[a-f0-9]{64}\z/D',
                (string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex')) !== 1) {
            return false;
        }
        // DEV must never bill LIVE; PROD must never bill a sandbox. Both routes
        // are checked, not only whichever price the shopper selected.
        try {
            $isdev = CommerceGiftDevE2EGate::is_dev_environment();
            $stripe = \local_subscriptions\payment\stripe\StripeConfiguration::get();
            $profile = (string)$stripe['profile'];
            $live = in_array($profile, ['live_ei', 'live_sas', 'live'], true);
            $key = (string)($stripe['secret_key'] ?? '');
            $publishable = (string)($stripe['publishable_key'] ?? '');
            $alfa = \local_subscriptions\payment\Provider::env('alfa');
            return $isdev
                ? $profile === 'test' && str_starts_with($key, 'sk_test_')
                    && str_starts_with($publishable, 'pk_test_') && $alfa === 'test'
                : $live && str_starts_with($key, 'sk_live_')
                    && str_starts_with($publishable, 'pk_live_') && $alfa === 'live';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function can_operate(): bool {
        return self::enabled() || CommerceGiftCommercialDevGate::can_operate();
    }

    public static function assert_buyer(\moodle_database $db, int $buyerid,
        ?string $buyeremail = null): void {
        if (!self::enabled()) {
            CommerceGiftCommercialDevGate::assert_buyer($db, $buyerid, $buyeremail);
            return;
        }
        if ($buyerid <= 2 || is_siteadmin($buyerid)) {
            throw new \DomainException('Gift buyer is not eligible.');
        }
        $user = $db->get_record('user', ['id' => $buyerid, 'deleted' => 0],
            'id,email,confirmed,suspended', IGNORE_MISSING);
        if (!$user || empty($user->confirmed) || !empty($user->suspended)
            || filter_var((string)$user->email, FILTER_VALIDATE_EMAIL) === false
            || ($buyeremail !== null && !hash_equals(strtolower(trim((string)$user->email)),
                strtolower(trim($buyeremail))))) {
            throw new \DomainException('Gift buyer identity is not valid.');
        }
    }

    public static function assert_recipient(\moodle_database $db, int $buyerid,
        string $buyeremail, string $recipientemail): void {
        if (!self::enabled()) {
            CommerceGiftCommercialDevGate::assert_recipient($db, $buyerid, $buyeremail, $recipientemail);
            return;
        }
        self::assert_buyer($db, $buyerid, $buyeremail);
        $email = strtolower(trim($recipientemail));
        if ($recipientemail !== $email || strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || hash_equals($email, strtolower(trim($buyeremail)))) {
            throw new \DomainException('Gift recipient must have a distinct valid mailbox.');
        }
        $rows = $db->get_records_sql(
            'SELECT id, confirmed, suspended FROM {user} WHERE deleted = 0 AND LOWER(email) = :mail',
            ['mail' => $email], 0, 3);
        if (count($rows) > 1) {
            throw new \DomainException('Ambiguous gift recipient identity.');
        }
        foreach ($rows as $user) {
            if ((int)$user->id <= 2 || is_siteadmin((int)$user->id)
                || (int)$user->id === $buyerid || empty($user->confirmed)
                || !empty($user->suspended)) {
                throw new \DomainException('Gift recipient account is not eligible.');
            }
        }
        // No user is created at purchase. Recipient proves mailbox with OTP.
    }
}
