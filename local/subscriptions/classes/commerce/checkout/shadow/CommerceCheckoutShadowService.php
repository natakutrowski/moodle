<?php

namespace local_subscriptions\commerce\checkout\shadow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;

/**
 * Projects a Legacy checkout into Commerce without contacting a provider.
 */
final class CommerceCheckoutShadowService {

    public function __construct(
        private readonly LegacyCommercePaymentRequestFactory $requestfactory
    ) {
    }

    public function evaluate(
        \stdClass $paymentrequest,
        string $paymentrequesttable,
        array $legacyoptions
    ): CommerceCheckoutShadowReport {
        $reference = sprintf(
            '%s:%d',
            $paymentrequesttable,
            (int)($paymentrequest->id ?? 0)
        );

        try {
            $request = $this->requestfactory->create(
                $paymentrequest,
                $paymentrequesttable,
                $legacyoptions
            );

            return new CommerceCheckoutShadowReport(
                $request->get_reference(),
                [
                    new CommerceCheckoutComparison(
                        'provider',
                        strtolower((string)($paymentrequest->payment_provider ?? '')),
                        (string)$request->get_preferred_provider()
                    ),
                    new CommerceCheckoutComparison(
                        'currency',
                        strtoupper((string)($paymentrequest->currency ?? '')),
                        $request->get_currency()
                    ),
                    new CommerceCheckoutComparison(
                        'amount_minor',
                        $this->legacy_amount_minor($paymentrequest),
                        $request->get_amount_minor()
                    ),
                    new CommerceCheckoutComparison(
                        'success_url',
                        $this->option_url($legacyoptions, ['success_url', 'returnurl']),
                        $request->get_return_url()
                    ),
                    new CommerceCheckoutComparison(
                        'cancel_url',
                        $this->option_url($legacyoptions, ['cancel_url', 'failurl']),
                        $request->get_cancel_url()
                    ),
                ]
            );
        } catch (\Throwable $exception) {
            return new CommerceCheckoutShadowReport(
                $reference,
                [],
                [[
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]]
            );
        }
    }

    private function legacy_amount_minor(\stdClass $paymentrequest): int {
        foreach (['locked_amount_minor', 'amount_minor', 'amount'] as $field) {
            if (!property_exists($paymentrequest, $field)) {
                continue;
            }

            $value = $paymentrequest->{$field};
            if ($field === 'amount' && is_numeric($value)) {
                return (int)round(((float)$value) * 100);
            }

            if (is_numeric($value)) {
                return (int)$value;
            }
        }

        return 0;
    }

    private function option_url(array $options, array $keys): ?string {
        foreach ($keys as $key) {
            $value = trim((string)($options[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
