<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\ProviderSelector;

/** Certifies the effective Alfa Commerce configuration without exposing secrets. */
final class CommerceAlfaConfigurationCertifier {
    public function certify(): array {
        $checks = [];
        $environment = Provider::env(Provider::ALFA) === 'live' ? 'live' : 'test';
        $prefix = 'alfa_' . $environment . '_';
        $base = trim((string)get_config('local_subscriptions', $prefix . 'api_base'));
        $token = trim((string)get_config('local_subscriptions', $prefix . 'token'));
        $username = trim((string)get_config('local_subscriptions', $prefix . 'username'));
        $password = trim((string)get_config('local_subscriptions', $prefix . 'password'));
        $credentials = $token !== '' || ($username !== '' && $password !== '');

        $this->check($checks, 'environment', in_array($environment, ['test', 'live'], true),
            'Alfa environment: ' . $environment . '.');
        $this->check($checks, 'api_base', $base !== '',
            $base !== '' ? 'Alfa API base is configured.' : 'Alfa API base is missing.');
        $this->check($checks, 'credentials', $credentials,
            $credentials ? 'Alfa credentials are configured.' : 'Alfa token or username/password is missing.');

        try {
            $provider = CommercePaymentProviderRegistryFactory::create()->get(Provider::ALFA);
            $this->check($checks, 'provider_registered', $provider->get_key() === Provider::ALFA,
                'Alfa is registered in the Commerce provider registry.');
            $this->check($checks, 'provider_available', $provider->is_available(),
                $provider->is_available() ? 'Alfa Commerce provider is available.' : 'Alfa Commerce provider is unavailable.');
            $capabilities = $provider->get_capabilities();
            $this->check($checks, 'rub_supported', $capabilities->supports_currency('RUB'),
                'Alfa supports RUB Commerce payments.');
            $this->check($checks, 'multi_item', $capabilities->supports_multiple_lines(),
                'Alfa supports aggregated multi-item payments.');
        } catch (\Throwable $exception) {
            $this->check($checks, 'provider_registry', false,
                'Unable to resolve Alfa from the Commerce registry: ' . $exception->getMessage());
        }

        $dummyplan = new \stdClass();
        $this->check($checks, 'provider_selection', ProviderSelector::chooseForPlan($dummyplan, 'RUB') === Provider::ALFA,
            'RUB checkout selects Alfa.');
        $this->check($checks, 'return_endpoint', is_file(__DIR__ . '/../../../../payment/return.php'),
            'Alfa return endpoint is present.');
        $this->check($checks, 'webhook_endpoint', is_file(__DIR__ . '/../../../../webhook/alfa.php'),
            'Alfa webhook endpoint is present.');

        return [
            'phase' => '7.95H4.10',
            'provider' => Provider::ALFA,
            'environment' => $environment,
            'certified' => !in_array(false, array_column($checks, 'passed'), true),
            'checks' => $checks,
        ];
    }

    private function check(array &$checks, string $key, bool $passed, string $message): void {
        $checks[] = ['key' => $key, 'passed' => $passed, 'status' => $passed ? 'PASS' : 'FAIL', 'message' => $message];
    }
}
