<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\security;

defined('MOODLE_INTERNAL') || die();

/** Builds and verifies signed Personal Offer bearer tokens. */
final class CommercePersonalOfferTokenCodec {
    public const VERSION = 1;

    public static function build(string $offeruuid): string {
        $offeruuid = strtolower(trim($offeruuid));
        if (!preg_match('/^[a-f0-9]{32}$/', $offeruuid)) {
            throw new \coding_exception('Invalid Personal Offer UUID.');
        }
        $payload = 'po' . self::VERSION . '.' . $offeruuid;
        return $payload . '.' . self::signature($payload);
    }

    /** @return array{version:int,offeruuid:string}|null */
    public static function parse_and_verify(string $token): ?array {
        $token = trim($token);
        if (!preg_match('/^po([0-9]+)\.([a-f0-9]{32})\.([a-f0-9]{64})$/', $token, $matches)) {
            return null;
        }
        $version = (int)$matches[1];
        if ($version !== self::VERSION) {
            return null;
        }
        $payload = 'po' . $version . '.' . $matches[2];
        if (!hash_equals(self::signature($payload), $matches[3])) {
            return null;
        }
        return ['version' => $version, 'offeruuid' => $matches[2]];
    }

    public static function fingerprint(string $token): string {
        return hash('sha256', trim($token));
    }

    private static function signature(string $payload): string {
        global $CFG;
        $secret = (string)($CFG->passwordsaltmain ?? $CFG->siteidentifier ?? 'moodle');
        return hash_hmac('sha256', $payload, $secret);
    }
}
