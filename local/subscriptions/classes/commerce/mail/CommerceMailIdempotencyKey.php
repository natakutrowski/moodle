<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Creates stable idempotency keys for transactional Commerce mail intentions.
 */
final class CommerceMailIdempotencyKey {

    public static function for_purchase(int $purchaseid, string $type): string {
        if ($purchaseid <= 0) {
            throw new \coding_exception(
                'A Commerce mail purchase idempotency key requires a positive purchase ID.'
            );
        }

        return self::normalise(
            'purchase:' . $purchaseid . ':' . CommerceMailType::normalise($type)
        );
    }

    public static function for_purchase_audit_copy(int $purchaseid, string $type): string {
        if ($purchaseid <= 0) {
            throw new \coding_exception(
                'A Commerce mail audit-copy idempotency key requires a positive purchase ID.'
            );
        }

        return self::normalise(
            'purchase:' . $purchaseid . ':' . CommerceMailType::normalise($type) . ':audit'
        );
    }

    public static function for_payment_attempt(int $attemptid, string $type): string {
        if ($attemptid <= 0) {
            throw new \coding_exception(
                'A Commerce mail payment idempotency key requires a positive attempt ID.'
            );
        }

        return self::normalise(
            'payment-attempt:' . $attemptid . ':' . CommerceMailType::normalise($type)
        );
    }

    public static function for_grant_source(string $sourcereference, string $type): string {
        $sourcereference = trim($sourcereference);
        if ($sourcereference === '') {
            throw new \coding_exception(
                'A Commerce mail grant source idempotency key requires a source reference.'
            );
        }

        return self::normalise(
            'grant-source:' . substr(hash('sha256', $sourcereference), 0, 40)
            . ':' . CommerceMailType::normalise($type)
        );
    }

    public static function for_grant(int $grantid, string $type): string {
        if ($grantid <= 0) {
            throw new \coding_exception(
                'A Commerce mail grant idempotency key requires a positive grant ID.'
            );
        }

        return self::normalise(
            'grant:' . $grantid . ':' . CommerceMailType::normalise($type)
        );
    }

    public static function normalise(string $key): string {
        $key = strtolower(trim($key));
        $key = preg_replace('/\s+/', '-', $key) ?? '';

        if (
            $key === ''
            || strlen($key) > 191
            || !preg_match('/^[a-z0-9][a-z0-9:._-]*$/', $key)
        ) {
            throw new \coding_exception(
                'Invalid Commerce transactional mail idempotency key.'
            );
        }

        return $key;
    }

    private function __construct() {
    }
}
