<?php

namespace local_subscriptions\digital\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\dto\DigitalPaymentProviderStatus;

/**
 * Resolves the status provider associated with a payment request.
 */
final class DigitalPaymentStatusProviderRegistry {

    /**
     * @var DigitalPaymentStatusProvider[]
     */
    private array $providers;

    public function __construct(
        ?array $providers = null
    ) {
        $providers ??= [
            new StripePaymentStatusProvider(),
            new AlfaPaymentStatusProvider(),
        ];

        foreach ($providers as $provider) {
            if (
                !$provider instanceof
                DigitalPaymentStatusProvider
            ) {
                throw new \coding_exception(
                    'Invalid digital payment status provider.'
                );
            }
        }

        $this->providers = array_values(
            $providers
        );
    }

    public function check(
        \stdClass $paymentrequest
    ): DigitalPaymentProviderStatus {
        $providerkey =
            strtolower(
                trim(
                    (string)(
                        $paymentrequest->payment_provider
                        ?? ''
                    )
                )
            );

        foreach ($this->providers as $provider) {
            if (
                $provider->supports(
                    $providerkey
                )
            ) {
                return
                    $provider->check(
                        $paymentrequest
                    );
            }
        }

        return
            DigitalPaymentProviderStatus::unknown(
                'Unsupported provider: ' .
                (
                    $providerkey !== ''
                        ? $providerkey
                        : '[empty]'
                )
            );
    }
}