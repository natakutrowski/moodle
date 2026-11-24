<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/** Stateless gift invitation secret. Persist only its SHA-256 fingerprint. */
final class CommerceGiftToken {
    public static function generate(): string {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    public static function fingerprint(string $token): string {
        if (preg_match('/\A[A-Za-z0-9_-]{43}\z/D', $token) !== 1) {
            throw new \InvalidArgumentException('Invalid gift token format.');
        }
        return hash('sha256', 'campusfr:gift:claim:v1:' . $token);
    }

    public static function matches(string $token, string $storedfingerprint): bool {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $storedfingerprint) !== 1) {
            return false;
        }
        try {
            return hash_equals($storedfingerprint, self::fingerprint($token));
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
