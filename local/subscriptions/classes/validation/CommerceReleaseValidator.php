<?php

namespace local_subscriptions\validation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\certification\CommerceCertificationMatrix;
use local_subscriptions\commerce\checkout\CommerceCheckoutFeatureToggle;
use local_subscriptions\commerce\checkout\CommerceCheckoutPersistenceService;
use local_subscriptions\commerce\checkout\CommerceCheckoutService;
use local_subscriptions\commerce\fulfillment\bridge\CommerceFulfillmentFeatureToggle;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\purchase\digital\DigitalPurchaseHandler;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPurchaseHandler;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Read-only preflight for the Commerce migration layer.
 */
final class CommerceReleaseValidator {

    private const TOGGLES = [
        'commerce_checkout_enabled',
        'commerce_fulfillment_enabled',
    ];

    public function validate(ValidationResult $result): void {
        $this->validate_classes($result);
        $this->validate_runtime($result);
        $this->validate_toggles($result);
        $this->validate_provider_configuration($result);
        $this->validate_matrix($result);
    }

    private function validate_classes(ValidationResult $result): void {
        $classes = [
            CommerceRuntimeFactory::class,
            CommerceCheckoutService::class,
            CommerceCheckoutPersistenceService::class,
            CommerceCheckoutFeatureToggle::class,
            CommerceFulfillmentFeatureToggle::class,
            CommerceCertificationMatrix::class,
        ];

        foreach ($classes as $class) {
            class_exists($class)
                ? $result->success('commerce_class', 'Classe Commerce disponible : ' . $class)
                : $result->error('commerce_class_missing', 'Classe Commerce absente : ' . $class);
        }
    }

    private function validate_runtime(ValidationResult $result): void {
        try {
            $runtime = CommerceRuntimeFactory::create();
            $purchasekeys = $runtime->purchase_handlers()->keys();
            $fulfillmentkeys = $runtime->fulfillment_handlers()->keys();
            $providerkeys = $runtime->payment_providers()->keys();

            $this->require_key($result, 'commerce_purchase_handler', SubscriptionPurchaseHandler::KEY, $purchasekeys);
            $this->require_key($result, 'commerce_purchase_handler', DigitalPurchaseHandler::KEY, $purchasekeys);
            $this->require_key($result, 'commerce_fulfillment_handler', SubscriptionEnrolmentFulfillmentHandler::KEY, $fulfillmentkeys);
            $this->require_key($result, 'commerce_fulfillment_handler', DigitalDownloadFulfillmentHandler::KEY, $fulfillmentkeys);
            $this->require_key($result, 'commerce_payment_provider', 'stripe', $providerkeys);
            $this->require_key($result, 'commerce_payment_provider', 'alfa', $providerkeys);

            $runtime->post_payment_bridge();
            $runtime->post_fulfillment();
            $result->success('commerce_runtime', 'Runtime Commerce construit avec ses bridges post-paiement.');
        } catch (\Throwable $exception) {
            $result->error('commerce_runtime_error', 'Impossible de construire le runtime Commerce.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function require_key(ValidationResult $result, string $code, string $key, array $keys): void {
        in_array($key, $keys, true)
            ? $result->success($code, 'Composant enregistré : ' . $key)
            : $result->error($code . '_missing', 'Composant non enregistré : ' . $key);
    }

    private function validate_toggles(ValidationResult $result): void {
        foreach (self::TOGGLES as $toggle) {
            $enabled = !empty(get_config('local_subscriptions', $toggle));
            $result->success('commerce_toggle', $toggle . ' = ' . ($enabled ? 'ON' : 'OFF'), [
                'toggle' => $toggle,
                'enabled' => $enabled,
            ]);
        }

        $checkoutenabled = $this->any_checkout_enabled();
        $fulfillmentenabled = !empty(get_config('local_subscriptions', 'commerce_fulfillment_enabled'));
        if ($checkoutenabled && !$fulfillmentenabled) {
            $result->warning(
                'commerce_checkout_without_fulfillment',
                'Les checkouts Commerce sont actifs tandis que le fulfillment Commerce reste désactivé. Le post-paiement Legacy doit donc rester opérationnel.'
            );
        }
    }

    private function validate_provider_configuration(ValidationResult $result): void {
        $registry = CommercePaymentProviderRegistryFactory::create();
        $requirements = [
            'stripe' => [
                'enabled' => $this->stripe_enabled(),
                'env' => $this->environment('stripe'),
                'required' => ['secret', 'webhook_secret'],
            ],
            'alfa' => [
                'enabled' => $this->alfa_enabled(),
                'env' => $this->environment('alfa'),
                'required_any' => [['token'], ['username', 'password']],
                'required' => ['api_base'],
            ],
        ];

        foreach ($requirements as $providerkey => $definition) {
            if (!$registry->has($providerkey)) {
                $result->error('commerce_provider_missing', 'Provider Commerce absent : ' . $providerkey);
                continue;
            }

            $provider = $registry->get($providerkey);
            $enabled = (bool)$definition['enabled'];
            $available = $provider->is_available();
            $context = ['provider' => $providerkey, 'environment' => $definition['env'], 'available' => $available];

            if ($enabled && !$available) {
                $result->error('commerce_provider_unavailable', 'Provider Commerce activé mais indisponible : ' . $providerkey, $context);
            } else if ($available) {
                $result->success('commerce_provider_available', 'Provider Commerce disponible : ' . $providerkey, $context);
            } else {
                $result->warning('commerce_provider_inactive', 'Provider Commerce non configuré ou indisponible : ' . $providerkey, $context);
            }

            foreach ($definition['required'] ?? [] as $suffix) {
                $this->validate_config_presence($result, $providerkey, $definition['env'], $suffix, $enabled);
            }

            if (!empty($definition['required_any'])) {
                $validalternative = false;
                foreach ($definition['required_any'] as $alternative) {
                    $validalternative = $validalternative || $this->all_config_present($providerkey, $definition['env'], $alternative);
                }
                if (!$validalternative && $enabled) {
                    $result->error('commerce_provider_credentials_missing', 'Identifiants incomplets pour ' . $providerkey . ' (' . $definition['env'] . ').');
                }
            }
        }
    }

    private function validate_matrix(ValidationResult $result): void {
        $scenarios = (new CommerceCertificationMatrix())->scenarios();
        foreach ($scenarios as $scenario) {
            $context = $scenario->to_array();
            $scenario->is_enabled()
                ? $result->success('commerce_scenario_enabled', 'Scénario prêt à certifier : ' . $scenario->get_key(), $context)
                : $result->warning('commerce_scenario_disabled', 'Scénario non activé : ' . $scenario->get_key(), $context);
        }
    }

    private function any_checkout_enabled(): bool {
        return $this->stripe_enabled() || $this->alfa_enabled();
    }

    private function stripe_enabled(): bool {
        return !empty(get_config(
            'local_subscriptions',
            'commerce_checkout_enabled'
        ));
    }

    private function alfa_enabled(): bool {
        return !empty(get_config(
            'local_subscriptions',
            'commerce_checkout_enabled'
        ));
    }

    private function environment(string $provider): string {
        return get_config('local_subscriptions', $provider . '_env') === 'live' ? 'live' : 'test';
    }

    private function validate_config_presence(ValidationResult $result, string $provider, string $environment, string $suffix, bool $required): void {
        $name = $provider . '_' . $environment . '_' . $suffix;
        $present = trim((string)get_config('local_subscriptions', $name)) !== '';
        if ($present) {
            $result->success('commerce_provider_config', 'Configuration présente : ' . $name);
        } else if ($required) {
            $result->error('commerce_provider_config_missing', 'Configuration requise absente : ' . $name);
        } else {
            $result->warning('commerce_provider_config_missing', 'Configuration absente : ' . $name);
        }
    }

    private function all_config_present(string $provider, string $environment, array $suffixes): bool {
        foreach ($suffixes as $suffix) {
            if (trim((string)get_config('local_subscriptions', $provider . '_' . $environment . '_' . $suffix)) === '') {
                return false;
            }
        }
        return true;
    }
}
