<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;
use moodle_database;

/**
 * Authoritative reconciliation engine for Native Alfa payments.
 *
 * It never marks a payment paid by administrative assertion. Alfa is queried
 * live and the existing EventRouter/Commerce fulfillment pipeline is reused.
 */
final class AlfaPaymentReconciliationService implements AlfaPaymentReconciliationEngineInterface {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePaymentRepository $payments,
        private readonly AlfaPaymentStatusProbeInterface $probe,
        private readonly AlfaPaymentReconciliationFinalizerInterface $finalizer
    ) {
    }

    public static function create(moodle_database $database): self {
        return new self(
            $database,
            new CommercePaymentRepository($database),
            new AlfaGatewayPaymentStatusProbe(),
            new EventRouterAlfaPaymentReconciliationFinalizer()
        );
    }

    public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $attempt = $this->payments->find($paymentid);
        if ($attempt === null) {
            throw new \moodle_exception('commerce_alfa_reconciliation_payment_not_found', 'local_subscriptions');
        }
        return $this->inspect_attempt($attempt);
    }

    public function inspect_purchase_reference(string $reference): AlfaPaymentReconciliationInspection {
        $reference = trim($reference);
        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['reference' => $reference],
            '*',
            MUST_EXIST
        );
        $attempts = $this->payments->find_for_purchase((string)$purchase->purchaseuuid);
        foreach ($attempts as $attempt) {
            if ($attempt->get_provider() === Provider::ALFA) {
                return $this->inspect_attempt($attempt);
            }
        }
        throw new \moodle_exception('commerce_alfa_reconciliation_attempt_not_found', 'local_subscriptions');
    }

    public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $inspection = $this->inspect_payment($paymentid);
        if ($inspection->alreadycomplete) {
            return $inspection;
        }
        if (!$inspection->reconcilable) {
            throw new \moodle_exception(
                'commerce_alfa_reconciliation_not_safe',
                'local_subscriptions',
                '',
                implode(', ', $inspection->blockers)
            );
        }

        $event = $inspection->provider->event;
        $event->meta['provider'] = Provider::ALFA;
        $event->meta['commerce_payment_id'] = (string)$inspection->paymentid;
        $event->meta['commerce_purchase_uuid'] = $inspection->purchaseuuid;
        $event->meta['commerce_reference'] = $inspection->purchasereference;
        $event->meta['reconciliation_source'] = 'alfa_payment_reconciliation';
        $event->meta['reconciliation_checked_at'] = time();

        // Do not wrap the complete post-payment pipeline in one delegated
        // transaction. The finalizer reaches fulfillment, transactional mail
        // and invoice generation, which own narrower transaction boundaries.
        // Keeping an outer transaction here can turn a recoverable inner mail
        // failure into an invalid commit for the confirmed payment flow.
        $this->finalizer->finalize($event);

        // Re-read Campus state without a second provider call. The Alfa status
        // used to authorize this execution remains the immutable evidence for
        // this reconciliation attempt.
        $afterattempt = $this->payments->find($paymentid);
        if ($afterattempt === null) {
            throw new \RuntimeException('The reconciled Commerce payment attempt disappeared.');
        }
        return $this->inspect_attempt($afterattempt, $inspection->provider);
    }

    private function inspect_attempt(
        CommercePaymentAttempt $attempt,
        ?AlfaPaymentProviderStatus $knownprovider = null
    ): AlfaPaymentReconciliationInspection {
        if ($attempt->get_provider() !== Provider::ALFA) {
            throw new \moodle_exception('commerce_alfa_reconciliation_wrong_provider', 'local_subscriptions');
        }
        $paymentid = (int)$attempt->get_id();
        $purchase = $this->database->get_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            ['purchaseuuid' => $attempt->get_purchase_uuid()],
            '*',
            MUST_EXIST
        );
        $orderid = trim((string)($attempt->get_provider_order_id() ?? $attempt->get_provider_reference() ?? ''));
        if ($orderid === '') {
            throw new \moodle_exception('commerce_alfa_reconciliation_missing_orderid', 'local_subscriptions');
        }

        $provider = $knownprovider ?? $this->probe->probe($orderid);
        $amountmatches = $provider->amountminor !== null
            && $provider->amountminor === $attempt->get_amount_minor()
            && $provider->amountminor === (int)$purchase->totalminor;
        $currencymatches = $provider->currency !== null
            && $provider->currency === $attempt->get_currency()
            && $provider->currency === strtoupper((string)$purchase->currency);
        $approvedmatches = $provider->approvedamountminor === null
            || $provider->approvedamountminor === $attempt->get_amount_minor();
        $providerrefunded = $provider->is_refunded_state();
        $expecteddepositedamountminor =
            $providerrefunded
                ? max(
                    0,
                    $attempt->get_amount_minor()
                        - (int)($provider->refundedamountminor ?? 0)
                )
                : $attempt->get_amount_minor();

        $refundamountmatches = !$providerrefunded
            || (
                $provider->refundedamountminor !== null
                && $provider->refundedamountminor >= 0
                && $provider->refundedamountminor
                    <= $attempt->get_amount_minor()
            );

        $depositedmatches = $provider->depositedamountminor === null
            || $provider->depositedamountminor
                === $expecteddepositedamountminor;

        // "providerpaid" historically means "financially coherent enough to
        // trust the provider state". A refunded Alfa payment still satisfies
        // that requirement when deposited + refunded = original amount.
        $providerpaid = $provider->is_financially_settled();

        $alreadycomplete = in_array($attempt->get_status(), [
                CommercePaymentAttemptStatus::PAID,
                CommercePaymentAttemptStatus::COMPLETED,
            ], true)
            && (string)$purchase->status === CommercePurchaseStatus::FULFILLED;

        $blockers = [];
        if (!$providerpaid) {
            $blockers[] = 'provider_not_paid';
        }
        if (!$amountmatches) {
            $blockers[] = 'amount_mismatch';
        }
        if (!$currencymatches) {
            $blockers[] = 'currency_mismatch';
        }
        if (!$approvedmatches) {
            $blockers[] = 'approved_amount_mismatch';
        }
        if (!$depositedmatches) {
            $blockers[] = 'deposited_amount_mismatch';
        }
        if (!$refundamountmatches) {
            $blockers[] = 'refund_amount_mismatch';
        }
        if (
            !$alreadycomplete
            && $providerpaid
            && $provider->event->type !== 'checkout_completed'
        ) {
            $blockers[] = 'provider_event_not_completed';
        }
        if (in_array($attempt->get_status(), [
            CommercePaymentAttemptStatus::REFUNDED,
            CommercePaymentAttemptStatus::CANCELLED,
            CommercePaymentAttemptStatus::FAILED,
            CommercePaymentAttemptStatus::ERROR,
        ], true)) {
            $blockers[] = 'campus_payment_terminal_' . $attempt->get_status();
        }

        return new AlfaPaymentReconciliationInspection(
            $paymentid,
            (int)$purchase->id,
            (string)$purchase->reference,
            (string)$purchase->purchaseuuid,
            $attempt->get_status(),
            (string)$purchase->status,
            $attempt->get_amount_minor(),
            $attempt->get_currency(),
            $orderid,
            $provider,
            $amountmatches,
            $currencymatches,
            $approvedmatches,
            $depositedmatches,
            $providerpaid,
            !$alreadycomplete && $blockers === [],
            $alreadycomplete,
            $blockers,
            $providerrefunded,
            $refundamountmatches,
            $expecteddepositedamountminor
        );
    }
}
