<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G5B4B5: DEV-only *unarmed* commercial rehearsal gate. Never public/PROD.
 * The site-bound opt-in must be explicitly set AFTER deploying and auditing.
 * Both payment gateways MUST be configured for test; live payments are denied.
 */
final class CommerceGiftCommercialDevGate {
    public const ENABLE_SETTING = 'commerce_gifts_dev_commercial_rehearsal';
    public const URL_SETTING = 'commerce_gifts_dev_commercial_wwwroot';
    // G6B2: strict, short-lived, site-bound rehearsal allowlist.
    public const BUYER_SETTING = 'commerce_gifts_dev_commercial_buyerid';
    public const EXISTING_RECIPIENT_SETTING = 'commerce_gifts_dev_commercial_existing_recipientid';
    public const GUEST_HMAC_SETTING = 'commerce_gifts_dev_commercial_guest_hmac';
    public const EXPIRES_SETTING = 'commerce_gifts_dev_commercial_expiresat';
    public const MAX_TTL = 120 * 60;

    public static function is_armed(): bool {
        global $CFG;
        if (!CommerceGiftDevE2EGate::is_dev_environment()
            || CommerceGiftFeatureToggle::RUNTIME_WIRED
            || !isset($CFG->wwwroot)
            || !str_starts_with((string)$CFG->wwwroot, 'https://')
            || (string)get_config('local_subscriptions', self::ENABLE_SETTING) !== '1'
            || !hash_equals((string)get_config('local_subscriptions', self::URL_SETTING),
                (string)$CFG->wwwroot)
            || (int)get_config('local_subscriptions', self::BUYER_SETTING) <= 2
            || (int)get_config('local_subscriptions', self::EXISTING_RECIPIENT_SETTING) <= 2
            || !preg_match('/\A[a-f0-9]{64}\z/D',
                (string)get_config('local_subscriptions', self::GUEST_HMAC_SETTING))
            || !preg_match('/\A[a-f0-9]{64}\z/D',
                (string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'))
            || (int)get_config('local_subscriptions', self::EXPIRES_SETTING) <= time()
            || (int)get_config('local_subscriptions', self::EXPIRES_SETTING) > time() + self::MAX_TTL) {
            return false;
        }
        try {
            $stripe = \local_subscriptions\payment\stripe\StripeConfiguration::get();
            return $stripe['profile'] === 'test'
                && str_starts_with($stripe['secret_key'], 'sk_test_')
                && str_starts_with($stripe['publishable_key'], 'pk_test_')
                && \local_subscriptions\payment\Provider::env('alfa') === 'test';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Retain the original G3D fixed-pair pilot unchanged as a separate path. */
    public static function can_operate(): bool {
        return CommerceGiftDevE2EGate::is_armed() || self::is_armed();
    }

    public static function require_operational(): void {
        if (!self::can_operate()) {
            throw new \DomainException('DEV commercial gifts are disabled.');
        }
    }

    /** Authoritative Moodle account, never a buyer ID or email from the browser. */
    public static function assert_buyer(\moodle_database $db, int $buyerid, ?string $buyeremail = null): void {
        // Never allow every real DEV customer merely because the rehearsal is armed.
        // Prefer the single designated commercial buyer, even if legacy G3D was
        // accidentally switched on; the two modes are kept mutually exclusive.
        if (self::is_armed()) {
            if ($buyerid !== (int)get_config('local_subscriptions', self::BUYER_SETTING)
                || CommerceGiftDevE2EGate::is_armed()) {
                throw new \DomainException('Buyer is not on the controlled DEV allowlist.');
            }
        } else if (CommerceGiftDevE2EGate::is_armed()
            && $buyerid === (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::BUYER_SETTING)) {
            CommerceGiftDevE2EGate::assert_test_account($buyerid, 'buyer');
        } else {
            throw new \DomainException('Buyer is not authorised for DEV gift rehearsal.');
        }
        if ($buyerid <= 2 || is_siteadmin($buyerid)) {
            throw new \DomainException('Buyer is not an eligible customer.');
        }
        $user = $db->get_record('user', ['id' => $buyerid, 'deleted' => 0],
            'id,email,confirmed,suspended', IGNORE_MISSING);
        if (!$user || empty($user->confirmed) || !empty($user->suspended)
            || filter_var((string)$user->email, FILTER_VALIDATE_EMAIL) === false
            || ($buyeremail !== null && !hash_equals(strtolower(trim((string)$user->email)),
                strtolower(trim($buyeremail))))) {
            throw new \DomainException('DEV gift buyer account is not confirmed or changed.');
        }
    }

    /** Ordinary recipient may be a confirmed customer or an unregistered mailbox. */
    public static function assert_recipient(\moodle_database $db, int $buyerid,
        string $buyeremail, string $recipientemail): void {
        if (!self::is_armed()) {
            throw new \DomainException('Recipient validation requires DEV commercial rehearsal.');
        }
        self::assert_buyer($db, $buyerid, $buyeremail);
        $email = strtolower(trim($recipientemail));
        if ($recipientemail !== $email || strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || hash_equals($email, strtolower(trim($buyeremail)))) {
            throw new \DomainException('Gift recipient must be a different valid mailbox.');
        }
        // Allow exactly two operator-controlled destinations: a confirmed
        // account or an as-yet unregistered test mailbox. Hash the guest email
        // in Moodle config; there is no persistent recipient allowlist table.
        $testrecipientid = (int)get_config('local_subscriptions', self::EXISTING_RECIPIENT_SETTING);
        $testrecipient = $db->get_record('user', ['id' => $testrecipientid, 'deleted' => 0],
            'id,email,confirmed,suspended', IGNORE_MISSING);
        $allowexisting = $testrecipient && !empty($testrecipient->confirmed)
            && empty($testrecipient->suspended) && !is_siteadmin($testrecipientid)
            && hash_equals(strtolower(trim((string)$testrecipient->email)), $email);
        $guesthash = (string)get_config('local_subscriptions', self::GUEST_HMAC_SETTING);
        $keyhex = (string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex');
        $allowguest = preg_match('/\A[a-f0-9]{64}\z/D', $guesthash) === 1
            && preg_match('/\A[a-f0-9]{64}\z/D', $keyhex) === 1
            && hash_equals($guesthash, hash_hmac('sha256',
                'campusfr:g6b2:guest:v1:' . $email, hex2bin($keyhex)));
        if (!$allowexisting && !$allowguest) {
            throw new \DomainException('Recipient is not on the controlled DEV allowlist.');
        }
        $matching = $db->get_records_sql('SELECT id, confirmed, suspended FROM {user} '
            . 'WHERE deleted = 0 AND LOWER(email) = :mail', ['mail' => $email], 0, 3);
        if ($allowguest && !$allowexisting && $matching) {
            throw new \DomainException('Guest test mailbox must not have a Moodle account.');
        }
        if ($allowexisting && (count($matching) !== 1
            || (int)reset($matching)->id !== $testrecipientid)) {
            throw new \DomainException('Existing test recipient identity changed.');
        }
        if (count($matching) > 1) {
            throw new \DomainException('Recipient email has ambiguous Moodle identities.');
        }
        foreach ($matching as $user) {
            if ((int)$user->id <= 2 || is_siteadmin((int)$user->id)
                || empty($user->confirmed) || !empty($user->suspended)
                || (int)$user->id === $buyerid) {
                throw new \DomainException('Existing recipient identity is not eligible.');
            }
        }
    }
}
