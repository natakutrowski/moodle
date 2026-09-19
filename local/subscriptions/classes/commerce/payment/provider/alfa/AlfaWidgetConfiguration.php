<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\alfa;

use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Public configuration for Alfa's official Payment Widget.
 *
 * The widget token is Alfa's public Payment Widget token intended to be
 * embedded in the merchant page. It is deliberately separate from the
 * credentials used by the server-side Alfa API.
 */
final class AlfaWidgetConfiguration {
    public static function is_enabled(): bool {
        return (bool)get_config(
            'local_subscriptions',
            'alfa_widget_enabled'
        );
    }

    public static function environment(): string {
        return Provider::env(Provider::ALFA) === 'live'
            ? 'live'
            : 'test';
    }

    public static function token(): string {
        return trim(
            (string)get_config(
                'local_subscriptions',
                'alfa_'
                . self::environment()
                . '_widget_token'
            )
        );
    }

    public static function is_available(): bool {
        return self::is_enabled()
            && self::token() !== '';
    }

    public static function script_url(): string {
        return self::environment() === 'live'
            ? 'https://acspayzonaecom.com/assets/alfa-payment.js'
            : 'https://testpay.alfabank.ru/assets/alfa-payment.js';
    }

    public static function gateway(): string {
        return self::environment() === 'live'
            ? 'payment'
            : 'test';
    }
}
