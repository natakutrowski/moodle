<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * G5B.4B2: short-lived allowlist for ONE operator-controlled unregistered
 * recipient mailbox in DEV. No account is created here, and the public
 * checkout, RUB payments and email dispatch remain closed.
 *
 * Never use this allowance as evidence of mailbox ownership. G5B.3B must
 * verify the inbox using a separate OTP before creating the account.
 */
final class CommerceGiftDevGuestRecipientPolicy {
    public const SWITCH = 'commerce_gifts_dev_guest_email_enabled';
    public const DIGEST = 'commerce_gifts_dev_guest_email_hmac';
    public const EXPIRES = 'commerce_gifts_dev_guest_email_expires';
    private const DOMAIN = 'campusfr:dev-guest-recipient:v1:';

    public static function normalize(string $raw): string {
        $email = strtolower(trim($raw));
        if ($email !== $raw || strlen($email) > 254
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \DomainException('Unregistered recipient email must be canonical.');
        }
        return $email;
    }

    public static function fingerprint(string $canonicalemail, string $keyhex): string {
        self::normalize($canonicalemail);
        if (preg_match('/\A[a-f0-9]{64}\z/D', $keyhex) !== 1) {
            throw new \DomainException('DEV recipient verification key is unavailable.');
        }
        return hash_hmac('sha256', self::DOMAIN . $canonicalemail, hex2bin($keyhex));
    }

    public static function permitted(string $canonicalemail, string $keyhex,
        string $alloweddigest, int $expires, int $now): bool {
        if ($now <= 0 || $expires <= $now || $expires > $now + 7200
            || preg_match('/\A[a-f0-9]{64}\z/D', $alloweddigest) !== 1) {
            return false;
        }
        try {
            return hash_equals($alloweddigest, self::fingerprint($canonicalemail, $keyhex));
        } catch (\DomainException $e) {
            return false;
        }
    }

    public static function is_enabled(): bool {
        return CommerceGiftDevE2EGate::is_dev_environment()
            && CommerceGiftDevE2EGate::is_armed()
            && (string)get_config('local_subscriptions', self::SWITCH) === '1';
    }

    public static function assert_allowed(\moodle_database $db, int $buyerid,
        string $buyeremail, string $recipientemail, int $now): void {
        if (!self::is_enabled() || $buyerid <= 2
            || (int)get_config('local_subscriptions', CommerceGiftDevE2EGate::BUYER_SETTING) !== $buyerid) {
            throw new \DomainException('Unregistered gift recipient test is not authorised.');
        }
        $recipientemail = self::normalize($recipientemail);
        if (!filter_var($buyeremail, FILTER_VALIDATE_EMAIL)
            || hash_equals($recipientemail, strtolower(trim($buyeremail)))) {
            throw new \DomainException('Buyer and gift recipient must be different.');
        }
        $key = trim((string)get_config('local_subscriptions', 'commerce_gifts_intent_key_hex'));
        if (!self::permitted($recipientemail, $key,
            (string)get_config('local_subscriptions', self::DIGEST),
            (int)get_config('local_subscriptions', self::EXPIRES), $now)) {
            throw new \DomainException('Unregistered recipient mailbox is not allowlisted for this DEV test.');
        }
        // Existing, suspended and unconfirmed Moodle accounts ALL block the
        // new-account path. Never overwrite or shadow an existing identity.
        if ($db->record_exists_sql('SELECT 1 FROM {user} WHERE deleted = 0 AND LOWER(email) = :mail',
                ['mail' => $recipientemail])) {
            throw new \DomainException('Recipient already has a Moodle identity. Use the existing-account path.');
        }
    }
}
