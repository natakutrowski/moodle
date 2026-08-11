<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\payment\dto\InternalEvent;

/** Mirrors normalized provider events into the matching Native payment attempt. */
final class CommercePaymentEventSynchronizer {
    public function __construct(
        private readonly CommercePaymentRepository $payments
    ) {
    }

    /**
     * Synchronize one provider event.
     *
     * @return bool True when a Native payment attempt was resolved and synchronized.
     */
    public function synchronize(InternalEvent $event): bool {
        $attempt = $this->resolve_attempt($event);
        if ($attempt === null) {
            $this->log('unresolved', $event);
            return false;
        }

        $this->enrich_event_identity($event, $attempt);

        $status = match ($event->type) {
            'checkout_completed', 'invoice_paid' => CommercePaymentAttemptStatus::PAID,
            'payment_failed', 'invoice_failed' => CommercePaymentAttemptStatus::FAILED,
            'checkout_expired' => CommercePaymentAttemptStatus::CANCELLED,
            default => null,
        };

        if ($status === null) {
            return false;
        }

        $this->assert_provider_identity($attempt, $event);

        // A duplicated webhook must never downgrade or recreate an already paid attempt.
        if ($attempt->get_status() === CommercePaymentAttemptStatus::PAID
                && $status === CommercePaymentAttemptStatus::PAID) {
            $this->log('already_paid', $event, $attempt);
            return true;
        }

        $transactionid = $this->resolve_transaction_id($event);
        $payload = [
            'event_type' => $event->type,
            'provider' => $event->meta['provider'] ?? null,
            'session' => $event->meta['session'] ?? null,
            'payment_intent' => $event->meta['payment_intent'] ?? null,
            'payment_status' => $event->meta['payment_status'] ?? null,
            'checkout_status' => $event->meta['checkout_status'] ?? null,
            'commerce_payment_id' => $attempt->get_id(),
            'commerce_purchase_uuid' => $attempt->get_purchase_uuid(),
            'synchronized_at' => time(),
        ];

        $this->payments->update_status(
            (int) $attempt->get_id(),
            $status,
            $transactionid,
            $payload,
            $status === CommercePaymentAttemptStatus::PAID ? time() : null
        );

        $this->log('synchronized', $event, $attempt, $status);
        return true;
    }

    /**
     * Make the resolved Native identity available to the rest of the same
     * webhook pipeline, including events resolved through provider fallback.
     */
    private function enrich_event_identity(
        InternalEvent $event,
        CommercePaymentAttempt $attempt
    ): void {
        $event->meta['commerce_payment_id'] = (string)$attempt->get_id();
        $event->meta['commerce_purchase_uuid'] = $attempt->get_purchase_uuid();
        $event->meta['provider'] = $attempt->get_provider();

        if (trim((string)($event->meta['provider_payment_id'] ?? '')) === '') {
            $event->meta['provider_payment_id'] = $attempt->get_provider_reference();
        }
    }

    private function resolve_attempt(InternalEvent $event): ?CommercePaymentAttempt {
        $paymentid = $this->resolve_payment_id($event);
        if ($paymentid !== null) {
            $attempt = $this->payments->find($paymentid);
            if ($attempt !== null) {
                return $attempt;
            }
        }

        $provider = strtolower(trim((string)($event->meta['provider'] ?? '')));
        if ($provider === '') {
            return null;
        }

        foreach (['session', 'provider_payment_id', 'alfa_order_id'] as $key) {
            $reference = trim((string)($event->meta[$key] ?? ''));
            if ($reference === '') {
                continue;
            }

            $attempt = $this->payments->find_by_provider_reference($provider, $reference)
                ?? $this->payments->find_by_provider_order_id($provider, $reference);

            if ($attempt !== null) {
                return $attempt;
            }
        }

        return null;
    }

    private function resolve_payment_id(InternalEvent $event): ?int {
        $value = $event->meta['commerce_payment_id'] ?? null;
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value) && (int)$value > 0) {
            return (int)$value;
        }
        return null;
    }

    private function resolve_transaction_id(InternalEvent $event): ?string {
        foreach (['payment_intent', 'transaction_id', 'session'] as $key) {
            $value = trim((string)($event->meta[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function assert_provider_identity(
        CommercePaymentAttempt $attempt,
        InternalEvent $event
    ): void {
        $provider = strtolower(trim((string)($event->meta['provider'] ?? '')));
        if ($provider !== '' && $provider !== $attempt->get_provider()) {
            throw new \RuntimeException(
                'Provider mismatch while synchronizing Commerce payment #' . $attempt->get_id() . '.'
            );
        }

        $purchaseuuid = strtolower(trim((string)($event->meta['commerce_purchase_uuid'] ?? '')));
        if ($purchaseuuid !== '' && $purchaseuuid !== $attempt->get_purchase_uuid()) {
            throw new \RuntimeException(
                'Purchase UUID mismatch while synchronizing Commerce payment #' . $attempt->get_id() . '.'
            );
        }

        $session = trim((string)($event->meta['session'] ?? ''));
        $providerreference = $attempt->get_provider_reference();
        if ($session !== '' && $providerreference !== null && $session !== $providerreference) {
            throw new \RuntimeException(
                'Provider reference mismatch while synchronizing Commerce payment #' . $attempt->get_id() . '.'
            );
        }
    }

    private function log(
        string $result,
        InternalEvent $event,
        ?CommercePaymentAttempt $attempt = null,
        ?string $status = null
    ): void {
        error_log('[local_subscriptions][commerce_payment_webhook] ' . json_encode([
            'result' => $result,
            'event_type' => $event->type,
            'provider' => $event->meta['provider'] ?? null,
            'commerce_payment_id' => $attempt?->get_id()
                ?? ($event->meta['commerce_payment_id'] ?? null),
            'commerce_purchase_uuid' => $attempt?->get_purchase_uuid()
                ?? ($event->meta['commerce_purchase_uuid'] ?? null),
            'session' => $event->meta['session'] ?? null,
            'target_status' => $status,
        ], JSON_UNESCAPED_SLASHES));
    }
}
