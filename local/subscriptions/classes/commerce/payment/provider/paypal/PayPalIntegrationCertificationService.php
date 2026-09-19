<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

use local_subscriptions\commerce\payment\method\CommercePaymentMethod;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\refund\CommerceRefundCapablePaymentProvider;
use local_subscriptions\commerce\payment\refund\CommerceRefundHistoryCapablePaymentProvider;

defined('MOODLE_INTERNAL') || die();

/**
 * Final structural + operational certification for Commerce 7.96F PayPal.
 *
 * This service never creates/captures/refunds a payment.
 */
final class PayPalIntegrationCertificationService {
    public function __construct(
        private readonly CommercePaymentProviderRegistry $providers,
        private readonly PayPalOperationalHealthService $health
    ) {
    }

    public function certify(
        bool $checkremote = false
    ): PayPalIntegrationCertification {
        $errors = [];
        $warnings = [];
        $checks = [];

        if (!$this->providers->has(PayPalCommercePaymentProvider::KEY)) {
            return new PayPalIntegrationCertification(
                false,
                ['paypal_provider_not_registered'],
                [],
                ['provider_registered' => false]
            );
        }

        $provider =
            $this->providers->get(
                PayPalCommercePaymentProvider::KEY
            );

        $capabilities =
            $provider->get_capabilities();

        $checks['provider_registered'] = true;
        $checks['method_paypal'] =
            $capabilities->supports_payment_method(
                CommercePaymentMethod::PAYPAL
            );
        $checks['redirect'] =
            $capabilities->supports_redirect();
        $checks['retrieval'] =
            $capabilities->supports_retrieval();
        $checks['refund_capability'] =
            $capabilities->supports_refunds();
        $checks['refund_contract'] =
            $provider
                instanceof CommerceRefundCapablePaymentProvider;
        $checks['refund_history_contract'] =
            $provider
                instanceof CommerceRefundHistoryCapablePaymentProvider;
        $checks['currencies'] =
            $capabilities->get_currencies() !== [];

        foreach (
            [
                'method_paypal',
                'redirect',
                'retrieval',
                'refund_capability',
                'refund_contract',
                'refund_history_contract',
                'currencies',
            ]
            as $required
        ) {
            if (empty($checks[$required])) {
                $errors[] =
                    'paypal_certification_'
                    . $required
                    . '_failed';
            }
        }

        $operational =
            $this->health->inspect(
                $checkremote
            );

        $checks['environment'] =
            $operational->environment;
        $checks['credentials'] =
            $operational->credentialsconfigured;
        $checks['webhook'] =
            $operational->webhookconfigured;
        $checks['oauth'] =
            $operational->oauthreachable;

        if (!$operational->credentialsconfigured) {
            $warnings[] =
                'paypal_credentials_missing';
        }

        if (!$operational->webhookconfigured) {
            $warnings[] =
                'paypal_webhook_missing';
        }

        if (
            $checkremote
            && !$operational->oauthreachable
        ) {
            $errors[] =
                'paypal_oauth_unreachable';
        }

        return new PayPalIntegrationCertification(
            $errors === [],
            array_values(
                array_unique($errors)
            ),
            array_values(
                array_unique($warnings)
            ),
            $checks
        );
    }
}
