<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider;

use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;
use local_subscriptions\payment\stripe\StripeConfiguration;
use local_subscriptions\payment\Provider;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentProviderOperationalStatusService {
    public function __construct(
        ?CommercePaymentPresentationPolicy $presentation = null
    ) {
        $this->presentation =
            $presentation
            ?? new CommercePaymentPresentationPolicy();
    }

    private readonly CommercePaymentPresentationPolicy $presentation;

    public function stripe(): CommercePaymentProviderOperationalStatus {
        $profile = StripeConfiguration::active_profile();
        $config = StripeConfiguration::get($profile);

        return new CommercePaymentProviderOperationalStatus(
            Provider::STRIPE,
            $profile,
            trim((string)$config['secret_key']) !== ''
                && trim((string)$config['publishable_key']) !== '',
            trim((string)$config['webhook_secret']) !== '',
            true,
            $this->presentation->is_provider_allowed(Provider::STRIPE),
            [
                'profilelabel' => StripeConfiguration::label($profile),
                'publishable' =>
                    trim((string)$config['publishable_key']) !== '',
                'secret' =>
                    trim((string)$config['secret_key']) !== '',
                'portal' =>
                    trim((string)$config['portal_configuration_id']) !== '',
            ]
        );
    }

    public function alfa(): CommercePaymentProviderOperationalStatus {
        $environment = strtolower(
            trim(
                (string)(
                    get_config(
                        'local_subscriptions',
                        'alfa_env'
                    )
                    ?: 'test'
                )
            )
        ) === 'live'
            ? 'live'
            : 'test';

        $prefix = 'alfa_' . $environment . '_';

        $username = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'username'
        ));
        $password = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'password'
        ));
        $token = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'token'
        ));
        $refundusername = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'refund_username'
        ));
        $refundpassword = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'refund_password'
        ));
        $webhook = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'webhook_secret'
        ));
        $apibase = trim((string)get_config(
            'local_subscriptions',
            $prefix . 'api_base'
        ));

        $paymentcredentials =
            $token !== ''
            || (
                $username !== ''
                && $password !== ''
            );

        $refundconfigured =
            (
                $refundusername !== ''
                && $refundpassword !== ''
            )
            || (
                $username !== ''
                && $password !== ''
            );

        return new CommercePaymentProviderOperationalStatus(
            Provider::ALFA,
            $environment,
            $apibase !== ''
                && $paymentcredentials,
            true,
            $refundconfigured,
            $this->presentation->is_provider_allowed(Provider::ALFA),
            [
                'apibase' => $apibase !== '',
                'token' => $token !== '',
                'usernamepassword' =>
                    $username !== ''
                    && $password !== '',
                'refundcredentials' =>
                    $refundconfigured,
            ]
        );
    }

    public function paypal(): CommercePaymentProviderOperationalStatus {
        $configuration = new PayPalGatewayConfiguration();

        return new CommercePaymentProviderOperationalStatus(
            Provider::PAYPAL,
            $configuration->get_environment(),
            $configuration->is_configured(),
            $configuration->get_webhook_id() !== null,
            $configuration->is_configured(),
            $this->presentation->is_provider_allowed(Provider::PAYPAL),
            [
                'clientid' =>
                    $configuration->get_client_id() !== null,
                'secret' =>
                    $configuration->get_client_secret() !== null,
            ]
        );
    }

    public function get(string $provider): CommercePaymentProviderOperationalStatus {
        return match (strtolower(trim($provider))) {
            Provider::STRIPE => $this->stripe(),
            Provider::ALFA => $this->alfa(),
            Provider::PAYPAL => $this->paypal(),
            default => throw new \coding_exception(
                'Unknown payment provider: ' . $provider
            ),
        };
    }
}
