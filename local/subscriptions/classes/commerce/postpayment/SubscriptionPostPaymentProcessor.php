<?php

namespace local_subscriptions\commerce\postpayment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\shadow\CommerceFulfillmentShadowService;
use local_subscriptions\commerce\legacy\SubscriptionPurchaseFactory;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\constants\Status;
use local_subscriptions\domain\PaymentService;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\payment\Provider;

/**
 * Commerce-controlled post-payment processor for subscription checkout events.
 *
 * During 7.93G the proven Legacy subscription domain service remains the
 * fulfillment gateway. Commerce owns eligibility, idempotence, observability
 * and the decision not to run a second fulfillment path.
 */
final class SubscriptionPostPaymentProcessor {

    public function __construct(
        private readonly ?CommercePostPaymentLogger $logger = null
    ) {
    }

    public function before_legacy(InternalEvent $event): CommercePostPaymentProcessingResult {
        if (!$this->supports($event)) {
            return CommercePostPaymentProcessingResult::unsupported();
        }

        $paymentrequest = $this->find_payment_request($event);
        if ($paymentrequest === null) {
            $this->get_logger()->log('before_legacy', 'payment_request_missing', $this->log_context($event));
            return CommercePostPaymentProcessingResult::legacy_required();
        }

        if (!$this->pilot_enabled($event, $paymentrequest)) {
            return CommercePostPaymentProcessingResult::legacy_required((int)$paymentrequest->id);
        }

        if ($this->is_fulfilled($paymentrequest)) {
            $this->get_logger()->log('before_legacy', 'already_processed', $this->log_context($event, $paymentrequest));
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

        $this->get_logger()->log('before_legacy', 'commerce_completed', array_merge(
            $this->log_context($event, $paymentrequest),
            [
                'commerce_status' => 'fulfilled',
                'subscription_id' => (int)$paymentrequest->subscriptionid,
                'gateway' => 'legacy_subscription_domain_adapter',
            ]
        ));

        return CommercePostPaymentProcessingResult::commerce_completed((int)$paymentrequest->id);
    }

    public function after_legacy(InternalEvent $event): void {
        if (!$this->supports($event) || !$this->shadow_enabled()) {
            return;
        }

        try {
            $paymentrequest = $this->find_payment_request($event);
            if ($paymentrequest === null || empty($paymentrequest->subscriptionid)) {
                $this->get_logger()->log('after_legacy', 'not_fulfilled', $this->log_context($event, $paymentrequest));
                return;
            }

            global $DB;
            $subscription = $DB->get_record(
                'user_subscription',
                ['id' => (int)$paymentrequest->subscriptionid],
                '*',
                IGNORE_MISSING
            );
            if (!$subscription) {
                $this->get_logger()->log('after_legacy', 'subscription_missing', $this->log_context($event, $paymentrequest));
                return;
            }

            $plan = $DB->get_record('subscription_plan', ['id' => (int)$subscription->planid], '*', IGNORE_MISSING);
            $user = $DB->get_record('user', ['id' => (int)$subscription->userid], '*', IGNORE_MISSING);
            $purchase = SubscriptionPurchaseFactory::from_legacy_records(
                $subscription,
                $paymentrequest,
                $plan ?: null,
                $user ?: null
            );
            $report = CommerceRuntimeFactory::create()->fulfillment_shadow()->inspect($purchase);

            $this->get_logger()->log(
                'after_legacy',
                $report->is_compatible() ? 'ready' : 'mismatch',
                array_merge($this->log_context($event, $paymentrequest), [
                    'commerce_status' => $report->is_fulfilled() ? 'fulfilled' : 'not_fulfilled',
                    'subscription_id' => (int)$subscription->id,
                    'issues' => $report->get_issues(),
                ])
            );
        } catch (\Throwable $exception) {
            $this->get_logger()->log('after_legacy', 'shadow_error', array_merge(
                $this->log_context($event),
                ['issues' => [get_class($exception) . ': ' . $exception->getMessage()]]
            ));
        }
    }

    private function supports(InternalEvent $event): bool {
        return $event->type === 'checkout_completed'
            && strtolower((string)($event->meta['payment_context'] ?? '')) !== 'digital_product';
    }

    private function pilot_enabled(InternalEvent $event, \stdClass $paymentrequest): bool {
        if (empty(get_config('local_subscriptions', 'commerce_fulfillment_enabled'))) {
            return false;
        }

        $provider = strtolower((string)($event->meta['provider'] ?? $paymentrequest->payment_provider ?? ''));
        $currency = strtoupper((string)($event->currency ?? $paymentrequest->currency ?? ''));

        if ($provider === Provider::STRIPE && $currency === 'EUR') {
            return !empty(get_config('local_subscriptions', 'commerce_checkout_subscription_stripe_eur_enabled'));
        }

        if ($provider === Provider::ALFA && $currency === 'RUB') {
            return !empty(get_config('local_subscriptions', 'commerce_checkout_subscription_alfa_rub_enabled'));
        }

        return false;
    }

    private function shadow_enabled(): bool {
        return !empty(get_config('local_subscriptions', 'commerce_checkout_shadow_enabled'));
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
