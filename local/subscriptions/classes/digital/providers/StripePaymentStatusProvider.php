<?php

namespace local_subscriptions\digital\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\dto\DigitalPaymentProviderStatus;

/**
 * Checks Stripe Checkout session statuses.
 */
final class StripePaymentStatusProvider
    implements DigitalPaymentStatusProvider {

    public function provider_key(): string {
        return 'stripe';
    }

    public function supports(
        string $provider
    ): bool {
        return
            strtolower(trim($provider)) ===
            $this->provider_key();
    }

    public function check(
        \stdClass $paymentrequest
    ): DigitalPaymentProviderStatus {
        global $CFG;

        $sessionid =
            trim(
                (string)(
                    $paymentrequest->sessionid
                    ?? ''
                )
            );

        if ($sessionid === '') {
            return
                DigitalPaymentProviderStatus::unknown(
                    'No sessionid available.'
                );
        }

        $environment =
            get_config(
                'local_subscriptions',
                'stripe_env'
            ) ?: 'test';

        $environment =
            $environment === 'live'
                ? 'live'
                : 'test';

        $secret =
            get_config(
                'local_subscriptions',
                'stripe_' .
                $environment .
                '_secret'
            ) ?: '';

        if ($secret === '') {
            return
                DigitalPaymentProviderStatus::error(
                    'Missing Stripe secret key for environment: ' .
                    $environment
                );
        }

        $autoload =
            $CFG->dirroot .
            '/local/subscriptions/vendor/autoload.php';

        if (!is_file($autoload)) {
            return
                DigitalPaymentProviderStatus::error(
                    'Stripe SDK autoload not found.'
                );
        }

        require_once($autoload);

        if (
            !class_exists(
                \Stripe\Stripe::class
            ) ||
            !class_exists(
                \Stripe\Checkout\Session::class
            )
        ) {
            return
                DigitalPaymentProviderStatus::error(
                    'Stripe SDK classes are unavailable.'
                );
        }

        try {
            \Stripe\Stripe::setApiKey(
                $secret
            );

            $session =
                \Stripe\Checkout\Session::retrieve(
                    $sessionid
                );
        } catch (\Throwable $exception) {
            return
                DigitalPaymentProviderStatus::error(
                    $this->safe_exception_message(
                        $exception
                    )
                );
        }

        $paymentstatus =
            strtolower(
                trim(
                    (string)(
                        $session->payment_status
                        ?? ''
                    )
                )
            );

        $sessionstatus =
            strtolower(
                trim(
                    (string)(
                        $session->status
                        ?? ''
                    )
                )
            );

        if ($paymentstatus === 'paid') {
            return
                DigitalPaymentProviderStatus::paid();
        }

        if ($sessionstatus === 'expired') {
            return
                DigitalPaymentProviderStatus::declined(
                    'Stripe Checkout session expired.'
                );
        }

        return
            DigitalPaymentProviderStatus::pending(
                'Stripe status: ' .
                (
                    $sessionstatus !== ''
                        ? $sessionstatus
                        : 'unknown'
                ) .
                ' / payment_status: ' .
                (
                    $paymentstatus !== ''
                        ? $paymentstatus
                        : 'unknown'
                )
            );
    }

    private function safe_exception_message(
        \Throwable $exception
    ): string {
        $message =
            trim(
                $exception->getMessage()
            );

        if ($message === '') {
            return
                'Stripe request failed: ' .
                get_class($exception);
        }

        $message =
            preg_replace(
                '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
                'Bearer [redacted]',
                $message
            ) ?? $message;

        $message =
            preg_replace(
                '/sk_(?:live|test)_[A-Za-z0-9]+/i',
                '[redacted Stripe key]',
                $message
            ) ?? $message;

        if (
            \core_text::strlen($message) >
            1000
        ) {
            $message =
                \core_text::substr(
                    $message,
                    0,
                    1000
                ) .
                '…';
        }

        return
            'Stripe request failed: ' .
            $message;
    }
}