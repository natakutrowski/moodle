<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads PayPal Sandbox / Live credentials without performing network calls.
 */
final class PayPalGatewayConfiguration {
    public const ENV_SANDBOX = 'sandbox';
    public const ENV_LIVE = 'live';

    public function get_environment(): string {
        $environment = strtolower(
            trim(
                (string)get_config(
                    'local_subscriptions',
                    'paypal_env'
                )
            )
        );

        return $environment === self::ENV_LIVE
            ? self::ENV_LIVE
            : self::ENV_SANDBOX;
    }

    public function get_api_base(): string {
        return $this->get_environment() === self::ENV_LIVE
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function get_web_sdk_url(): string {
        return $this->get_environment() === self::ENV_LIVE
            ? 'https://www.paypal.com/web-sdk/v6/core'
            : 'https://www.sandbox.paypal.com/web-sdk/v6/core';
    }

    public function get_client_id(): ?string {
        return $this->read_secret('client_id');
    }

    public function get_client_secret(): ?string {
        return $this->read_secret('client_secret');
    }

    public function get_webhook_id(): ?string {
        return $this->read_secret('webhook_id');
    }

    public function is_configured(): bool {
        return $this->get_client_id() !== null
            && $this->get_client_secret() !== null;
    }

    private function read_secret(string $suffix): ?string {
        $value = trim(
            (string)get_config(
                'local_subscriptions',
                'paypal_'
                    . $this->get_environment()
                    . '_'
                    . $suffix
            )
        );

        return $value !== ''
            ? $value
            : null;
    }
}
