<?php

namespace local_subscriptions\commerce\postpayment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\shadow\CommerceFulfillmentShadowService;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteBridge;
use local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\domain\PaymentService;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\Provider;

/**
 * Commerce post-payment processor for Subscription Stripe/EUR and Alfa/RUB.
 *
 * The processor owns routing and idempotency. The existing Subscription domain service remains the fulfillment
 * implementation behind the Commerce bridge.
 */

final class SubscriptionPostPaymentProcessor {

    public function __construct(
        private readonly ?CommercePostPaymentLogger $logger = null
    ) {
    }

    public function process(
        InternalEvent $event
    ): CommercePostPaymentProcessingResult {
        if (!$this->supports($event)) {
            return CommercePostPaymentProcessingResult::unsupported();
        }

        $paymentrequest = $this->find_payment_request($event);
        if ($paymentrequest === null) {
            $this->get_logger()->log('process', 'payment_request_missing', $this->log_context($event));
            return CommercePostPaymentProcessingResult::legacy_required();
        }

        if (!$this->commerce_enabled_for($event, $paymentrequest)) {
            return CommercePostPaymentProcessingResult::legacy_required((int)$paymentrequest->id);
        }

        if ($this->is_fulfilled($paymentrequest)) {
            $this->get_logger()->log('process', 'already_processed', $this->log_context($event, $paymentrequest));
            CommerceDualWriteBridge::subscription((int)$paymentrequest->subscriptionid, 'subscription_postpayment_already_processed');
            return CommercePostPaymentProcessingResult::already_processed((int)$paymentrequest->id);
        }

        // Transitional gateway: preserve every legacy operation rule while
        // Commerce owns routing and guarantees a single execution path.
        PaymentService::on_checkout_completed($event);

        $paymentrequest = $this->find_payment_request($event);
        if ($paymentrequest === null || !$this->is_fulfilled($paymentrequest)) {
            throw new \RuntimeException(
                'Commerce-controlled subscription fulfillment did not create or link a subscription.'
            );
        }

        $this->get_logger()->log('process', 'commerce_completed', array_merge(
            $this->log_context($event, $paymentrequest),
            [
                'commerce_status' => 'fulfilled',
                'subscription_id' => (int)$paymentrequest->subscriptionid,
                'gateway' => 'legacy_subscription_domain_adapter',
            ]
        ));

        CommerceDualWriteBridge::subscription((int)$paymentrequest->subscriptionid, 'subscription_postpayment_completed');

        return CommercePostPaymentProcessingResult::commerce_completed((int)$paymentrequest->id);
    }

    private function supports(InternalEvent $event): bool {
        return $event->type === 'checkout_completed'
            && strtolower((string)($event->meta['payment_context'] ?? '')) !== 'digital_product';
    }

    private function commerce_enabled_for(
        InternalEvent $event,
        \stdClass $paymentrequest
    ): bool {
        if (empty(get_config(
            'local_subscriptions',
            'commerce_fulfillment_enabled'
        ))) {
            return false;
        }

        $provider = strtolower((string)(
            $event->meta['provider']
            ?? $paymentrequest->payment_provider
            ?? ''
        ));

        $currency = strtoupper((string)(
            $event->currency
            ?? $paymentrequest->currency
            ?? ''
        ));

        return ($provider === Provider::STRIPE && $currency === 'EUR')
            || ($provider === Provider::ALFA && $currency === 'RUB');
    }

    private function find_payment_request(InternalEvent $event): ?\stdClass {
        global $DB;

        if (!empty($event->payment_request_id)) {
            $record = $DB->get_record(
                'subscription_payment_request',
                ['id' => (int)$event->payment_request_id],
                '*',
                IGNORE_MISSING
            );
            if ($record) {
                return $record;
            }
        }

        $sessionid = trim((string)($event->meta['session'] ?? ''));
        if ($sessionid === '') {
            return null;
        }

        $record = $DB->get_record(
            'subscription_payment_request',
            ['sessionid' => $sessionid],
            '*',
            IGNORE_MISSING
        );

        if (!$record) {
            $record = $DB->get_record(
                'subscription_payment_request',
                ['transactionid' => $sessionid],
                '*',
                IGNORE_MISSING
            );
        }

        return $record ?: null;
    }

    private function is_fulfilled(\stdClass $paymentrequest): bool {
        if (!in_array((string)($paymentrequest->status ?? ''), [Status::PAID, Status::COMPLETED], true)) {
            return false;
        }

        if (empty($paymentrequest->subscriptionid)) {
            return false;
        }

        global $DB;
        return $DB->record_exists('user_subscription', ['id' => (int)$paymentrequest->subscriptionid]);
    }

    private function log_context(InternalEvent $event, ?\stdClass $paymentrequest = null): array {
        $requestid = $paymentrequest !== null
            ? (int)$paymentrequest->id
            : (!empty($event->payment_request_id) ? (int)$event->payment_request_id : null);
        $provider = strtolower((string)($event->meta['provider'] ?? $paymentrequest->payment_provider ?? 'unknown'));

        return [
            'provider' => $provider,
            'event_type' => $event->type,
            'payment_context' => 'subscription',
            'payment_request_id' => $requestid,
            'currency' => strtoupper((string)($event->currency ?? $paymentrequest->currency ?? '')),
            'legacy_status' => $paymentrequest->status ?? null,
            'correlation_id' => substr(hash('sha256', implode('|', [
                $provider,
                $event->type,
                (string)($requestid ?? 0),
                (string)($event->meta['session'] ?? ''),
            ])), 0, 16),
        ];
    }

    private function get_logger(): CommercePostPaymentLogger {
        return $this->logger ?? new CommercePostPaymentLogger();
    }
}
