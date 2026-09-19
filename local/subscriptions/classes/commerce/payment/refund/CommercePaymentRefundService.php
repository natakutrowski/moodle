<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\payment\provider\CommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderException;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;

defined('MOODLE_INTERNAL') || die();

/**
 * Single Commerce entry point for provider refunds.
 *
 * E6 intentionally exposes no refund for Stripe/Alfa yet: both must first
 * implement the optional contract and advertise the matching capability.
 */
final class CommercePaymentRefundService {
    public function __construct(
        private readonly CommercePaymentProviderRegistry $providers
    ) {
    }

    public function is_supported(string $providerkey): bool {
        $provider = $this->find_provider($providerkey);

        return $provider !== null
            && $provider->is_available()
            && $provider->get_capabilities()->supports_refunds()
            && $provider instanceof CommerceRefundCapablePaymentProvider;
    }

    public function refund(
        string $providerkey,
        CommercePaymentRefundRequest $request,
        CommercePaymentProviderContext $context
    ): CommercePaymentRefundResult {
        $provider = $this->find_provider($providerkey);

        if ($provider === null) {
            throw new CommercePaymentRefundException(
                'Unknown Commerce payment provider.',
                'refund_provider_unknown',
                strtolower(trim($providerkey))
            );
        }

        if (!$provider->is_available()) {
            throw new CommercePaymentRefundException(
                'The Commerce payment provider is unavailable.',
                'refund_provider_unavailable',
                $provider->get_key()
            );
        }

        if (
            !$provider->get_capabilities()->supports_refunds()
            || !$provider instanceof CommerceRefundCapablePaymentProvider
        ) {
            throw new CommercePaymentRefundException(
                'Refunds are not certified for this Commerce payment provider.',
                'refund_not_supported',
                $provider->get_key(),
                [
                    'capability' =>
                        $provider->get_capabilities()->supports_refunds(),
                    'contract' =>
                        $provider instanceof CommerceRefundCapablePaymentProvider,
                ]
            );
        }

        try {
            return $provider->refund($request, $context);
        } catch (CommercePaymentRefundException $exception) {
            throw $exception;
        } catch (CommercePaymentProviderException $exception) {
            $providermessage = $this->provider_failure_message(
                $exception
            );

            throw new CommercePaymentRefundException(
                $providermessage,
                $exception->get_provider_code()
                    ?? 'refund_provider_failed',
                $exception->get_provider_key()
                    ?? $provider->get_key(),
                array_merge(
                    [
                        'paymentreference' =>
                            $request->get_payment_reference(),
                        'providerpaymentid' =>
                            $request->get_provider_payment_id(),
                        'currency' =>
                            $request->get_currency(),
                        'amountminor' =>
                            $request->get_amount_minor(),
                        'providercode' =>
                            $exception->get_provider_code(),
                    ],
                    $exception->get_context()
                ),
                $exception
            );
        } catch (\Throwable $exception) {
            throw new CommercePaymentRefundException(
                $this->provider_failure_message($exception),
                'refund_provider_failed',
                $provider->get_key(),
                [
                    'paymentreference' =>
                        $request->get_payment_reference(),
                    'providerpaymentid' =>
                        $request->get_provider_payment_id(),
                    'currency' =>
                        $request->get_currency(),
                    'amountminor' =>
                        $request->get_amount_minor(),
                    'exception' =>
                        $exception::class,
                ],
                $exception
            );
        }
    }

    /**
     * Return the useful provider failure chain without exposing a stacktrace.
     */
    private function provider_failure_message(
        \Throwable $exception
    ): string {
        $messages = [];
        $current = $exception;
        $depth = 0;

        while ($current !== null && $depth < 5) {
            $message = trim($current->getMessage());

            if (
                $message !== ''
                && !in_array($message, $messages, true)
            ) {
                $messages[] = $message;
            }

            $current = $current->getPrevious();
            $depth++;
        }

        if ($messages === []) {
            return 'The Commerce refund operation failed.';
        }

        return implode(' — ', $messages);
    }

    private function find_provider(
        string $providerkey
    ): ?CommercePaymentProvider {
        $providerkey = strtolower(trim($providerkey));

        foreach ($this->providers->all() as $provider) {
            if ($provider->get_key() === $providerkey) {
                return $provider;
            }
        }

        return null;
    }
}
