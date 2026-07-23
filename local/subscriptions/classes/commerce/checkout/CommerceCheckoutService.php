<?php

namespace local_subscriptions\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\shadow\CommerceCheckoutShadowService;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;
use local_subscriptions\payment\PaymentGatewayFactory;

/**
 * Transitional common checkout facade.
 */
final class CommerceCheckoutService {

    public function __construct(
        private readonly CommerceCheckoutFeatureToggle $toggle,
        private readonly CommerceCheckoutEligibility $eligibility,
        private readonly CommerceCheckoutShadowService $shadowservice,
        private readonly LegacyCommercePaymentRequestFactory $legacyrequestfactory,
        private readonly CommercePaymentProviderContextFactory $contextfactory,
        private readonly CommercePaymentOrchestrator $paymentorchestrator
    ) {
    }

    public function initialize(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $legacyoptions,
        string $source,
        bool $live
    ): CommerceCheckoutResult {
        if ($this->toggle->is_shadow_enabled()) {
            $this->log_shadow(
                $this->shadowservice->evaluate($paymentrequest, $paymentrequesttable, $legacyoptions),
                $source
            );
        }

        if ($this->should_use_commerce($paymentrequest, $paymentrequesttable)) {
            return $this->initialize_commerce(
                $paymentrequest,
                $paymentrequesttable,
                $legacyoptions,
                $source,
                $live
            );
        }

        return $this->initialize_legacy($paymentrequest, $legacyoptions, $source);
    }

    private function should_use_commerce(\stdClass $request, string $table): bool {
        return (
            $this->toggle->is_digital_stripe_eur_enabled()
            && $this->eligibility->is_digital_stripe_eur($request, $table)
        ) || (
            $this->toggle->is_subscription_stripe_eur_enabled()
            && $this->eligibility->is_subscription_stripe_eur($request, $table)
        ) || (
            $this->toggle->is_digital_alfa_rub_enabled()
            && $this->eligibility->is_digital_alfa_rub($request, $table)
        ) || (
            $this->toggle->is_subscription_alfa_rub_enabled()
            && $this->eligibility->is_subscription_alfa_rub($request, $table)
        );
    }

    private function initialize_legacy(\stdClass $request, array $options, string $source): CommerceCheckoutResult {
        $provider = strtolower(trim((string)($request->payment_provider ?? '')));
        if ($provider === '') {
            throw new \RuntimeException('The Legacy payment request provider is missing.');
        }

        $legacyresult = PaymentGatewayFactory::for($provider)->create_checkout_session($request, $options);

        return new CommerceCheckoutResult(
            CommerceCheckoutResult::ENGINE_LEGACY,
            $this->extract_legacy_redirect_url($legacyresult),
            $this->extract_legacy_provider_payment_id($legacyresult),
            ['source' => $source]
        );
    }

    private function initialize_commerce(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $legacyoptions,
        string $source,
        bool $live
    ): CommerceCheckoutResult {
        $request = $this->legacyrequestfactory->create($paymentrequest, $paymentrequesttable, $legacyoptions);
        $context = $this->contextfactory->create(
            $request,
            $live,
            ['checkout_source' => $source, 'migration_phase' => '7.93F.4-F.6']
        );
        $initialization = $this->paymentorchestrator->initialize($request, $context);
        $paymentresult = $initialization->get_payment_result();
        $redirecturl = $paymentresult?->get_action()?->get_url();

        if ($redirecturl === null) {
            throw new \RuntimeException('The Commerce checkout did not return a redirect URL.');
        }

        return new CommerceCheckoutResult(
            CommerceCheckoutResult::ENGINE_COMMERCE,
            $redirecturl,
            $paymentresult->get_provider_payment_id(),
            [
                'source' => $source,
                'request_reference' => $request->get_reference(),
                'provider' => $initialization->get_provider_key(),
                'duration_ms' => $initialization->get_duration_milliseconds(),
            ]
        );
    }

    private function extract_legacy_redirect_url(mixed $result): string {
        if (is_string($result)) {
            $url = $result;
        } elseif (is_array($result)) {
            $url = $result['redirect_url'] ?? $result['url'] ?? '';
        } elseif (is_object($result) && property_exists($result, 'redirect_url')) {
            $url = (string)$result->redirect_url;
        } elseif (is_object($result) && method_exists($result, 'getUrl')) {
            $url = (string)$result->getUrl();
        } elseif (is_object($result) && method_exists($result, 'get_redirect_url')) {
            $url = (string)$result->get_redirect_url();
        } else {
            $url = '';
        }
        if (trim($url) === '') {
            throw new \RuntimeException('The Legacy checkout did not return a redirect URL.');
        }
        return $url;
    }

    private function extract_legacy_provider_payment_id(mixed $result): ?string {
        if (is_array($result)) {
            $value = $result['provider_session_id'] ?? $result['provider_payment_id'] ?? $result['sessionid'] ?? null;
        } elseif (is_object($result) && property_exists($result, 'provider_session_id')) {
            $value = $result->provider_session_id;
        } elseif (is_object($result) && property_exists($result, 'provider_payment_id')) {
            $value = $result->provider_payment_id;
        } elseif (is_object($result) && method_exists($result, 'get_provider_payment_id')) {
            $value = $result->get_provider_payment_id();
        } else {
            $value = null;
        }
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }

    private function log_shadow(
        \local_subscriptions\commerce\checkout\shadow\CommerceCheckoutShadowReport $report,
        string $source
    ): void {
        $payload = json_encode($report->to_array(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        error_log(sprintf(
            '[Commerce checkout shadow][%s][%s] %s',
            $source,
            $report->is_compatible() ? 'OK' : 'DIFF',
            $payload ?: '{}'
        ));
    }
}
