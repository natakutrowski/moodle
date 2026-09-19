<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\method;

defined('MOODLE_INTERNAL') || die();

/**
 * Stable customer-facing payment method keys.
 *
 * A payment method is deliberately not a PSP/provider. For example Stripe may
 * provide card, Apple Pay and Google Pay without any of those becoming a
 * Commerce payment provider.
 */
final class CommercePaymentMethod {
    public const CARD = 'card';
    public const APPLE_PAY = 'apple_pay';
    public const GOOGLE_PAY = 'google_pay';
    public const PAYPAL = 'paypal';
    public const LINK = 'link';
    public const KLARNA = 'klarna';
    public const ALFA_PAY = 'alfa_pay';
    public const SBP = 'sbp';
    public const SBERPAY = 'sberpay';
    public const MIR_PAY = 'mir_pay';

    public const KNOWN = [
        self::CARD,
        self::APPLE_PAY,
        self::GOOGLE_PAY,
        self::PAYPAL,
        self::LINK,
        self::KLARNA,
        self::ALFA_PAY,
        self::SBP,
        self::SBERPAY,
        self::MIR_PAY,
    ];

    public static function normalise(string $method): string {
        $method = strtolower(trim($method));

        if (
            $method === ''
            || !preg_match('/^[a-z][a-z0-9_]*$/', $method)
        ) {
            throw new \coding_exception(
                'Invalid Commerce payment method key: ' . $method
            );
        }

        return $method;
    }

    public static function is_known(string $method): bool {
        return in_array(
            self::normalise($method),
            self::KNOWN,
            true
        );
    }
}
