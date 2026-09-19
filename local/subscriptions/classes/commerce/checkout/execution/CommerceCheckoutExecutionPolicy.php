<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\execution;

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\alfa\AlfaWidgetConfiguration;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines where a payment method is executed in the current checkout generation.
 *
 * H1 deliberately preserves the certified hosted Card/PayPal flows. Wallets,
 * Link and Klarna require the Campus embedded executor introduced progressively
 * by the following H phases.
 */
final class CommerceCheckoutExecutionPolicy {
    public static function mode_for_method(
        string $method
    ): string {
        $method =
            CommercePaymentMethod::normalise(
                $method
            );

        return match ($method) {
            CommercePaymentMethod::PAYPAL,
            CommercePaymentMethod::CARD,
            CommercePaymentMethod::APPLE_PAY,
            CommercePaymentMethod::GOOGLE_PAY,
            CommercePaymentMethod::LINK,
            CommercePaymentMethod::KLARNA,
            CommercePaymentMethod::SBP =>
                CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,

            default =>
                CommerceCheckoutExecutionMode::PROVIDER_HOSTED,
        };
    }

    /**
     * Whether the method already has an executable path in the current H1 UI.
     */
    public static function mode_for_route(
        string $method,
        string $provider
    ): string {
        $method = CommercePaymentMethod::normalise($method);
        $provider = strtolower(trim($provider));

        if (
            $provider === 'paypal'
            && $method === CommercePaymentMethod::PAYPAL
        ) {
            return CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
        }

        if (
            $provider === 'stripe'
            && in_array(
                $method,
                [
                    CommercePaymentMethod::CARD,
                    CommercePaymentMethod::APPLE_PAY,
                    CommercePaymentMethod::GOOGLE_PAY,
                    CommercePaymentMethod::LINK,
                    CommercePaymentMethod::KLARNA,
                ],
                true
            )
        ) {
            return CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
        }

        if (
            $provider === 'alfa'
            && $method === CommercePaymentMethod::CARD
            && AlfaWidgetConfiguration::is_available()
        ) {
            return CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
        }

        if (
            $provider === 'alfa'
            && $method === CommercePaymentMethod::SBP
        ) {
            return CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
        }

        return CommerceCheckoutExecutionMode::PROVIDER_HOSTED;
    }

    public static function is_executable_now(
        string $method
    ): bool {
        $method = CommercePaymentMethod::normalise($method);

        return in_array(
            $method,
            [
                CommercePaymentMethod::CARD,
                CommercePaymentMethod::PAYPAL,
                CommercePaymentMethod::APPLE_PAY,
                CommercePaymentMethod::GOOGLE_PAY,
                CommercePaymentMethod::LINK,
                CommercePaymentMethod::KLARNA,
                CommercePaymentMethod::SBP,
            ],
            true
        )
            || self::mode_for_method($method)
                === CommerceCheckoutExecutionMode::PROVIDER_HOSTED;
    }

    public static function requires_embedded_executor(
        string $method
    ): bool {
        return self::mode_for_method($method)
            === CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED;
    }

    private function __construct() {
    }
}
