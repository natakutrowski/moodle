<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\paypal;

use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttempt;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;
use local_subscriptions\commerce\payment\provider\paypal\PayPalRestPaymentGateway;
use local_subscriptions\commerce\payment\provider\paypal\PayPalReturnCaptureService;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\Provider;
use moodle_database;

defined('MOODLE_INTERNAL') || die();

final class PayPalPaymentReconciliationService {
    public function __construct(
        private readonly moodle_database $database,
        private readonly CommercePaymentRepository $payments,
        private readonly CommercePaymentRefundRepository $refunds,
        private readonly PayPalRestPaymentGateway $gateway,
        private readonly PayPalReturnCaptureService $finalizer
    ) {
    }

    public static function create(
        moodle_database $database
    ): self {
        $configuration =
            new PayPalGatewayConfiguration();

        return new self(
            $database,
            new CommercePaymentRepository(
                $database
            ),
            new CommercePaymentRefundRepository(
                $database
            ),
            new PayPalRestPaymentGateway(
                $configuration
            ),
            PayPalReturnCaptureService::create(
                $database
            )
        );
    }

    public function inspect_payment(
        int $paymentid
    ): PayPalPaymentReconciliationInspection {
        $attempt =
            $this->payments->find(
                $paymentid
            );

        if ($attempt === null) {
            throw new \moodle_exception(
                'commerce_paypal_reconciliation_payment_not_found',
                'local_subscriptions'
            );
        }

        return $this->inspect_attempt(
            $attempt
        );
    }

    public function inspect_purchase_reference(
        string $reference
    ): PayPalPaymentReconciliationInspection {
        $purchase =
            $this->database->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                [
                    'reference' =>
                        trim($reference),
                ],
                '*',
                MUST_EXIST
            );

        $attempts =
            $this->payments->find_for_purchase(
                (string)$purchase->purchaseuuid
            );

        foreach ($attempts as $attempt) {
            if (
                $attempt->get_provider()
                    === Provider::PAYPAL
            ) {
                return $this->inspect_attempt(
                    $attempt
                );
            }
        }

        throw new \moodle_exception(
            'commerce_paypal_reconciliation_attempt_not_found',
            'local_subscriptions'
        );
    }

    public function reconcile_payment(
        int $paymentid
    ): PayPalPaymentReconciliationInspection {
        $inspection =
            $this->inspect_payment(
                $paymentid
            );

        if ($inspection->alreadycomplete) {
            return $inspection;
        }

        if (!$inspection->reconcilable) {
            throw new \moodle_exception(
                'commerce_paypal_reconciliation_not_safe',
                'local_subscriptions',
                '',
                implode(
                    ', ',
                    $inspection->blockers
                )
            );
        }

        $this->finalizer->capture(
            $inspection->paymentid,
            $inspection->providerorderid
        );

        $after =
            $this->payments->find(
                $paymentid
            );

        if ($after === null) {
            throw new \RuntimeException(
                'PayPal payment disappeared after reconciliation.'
            );
        }

        return $this->inspect_attempt(
            $after
        );
    }

    private function inspect_attempt(
        CommercePaymentAttempt $attempt
    ): PayPalPaymentReconciliationInspection {
        if (
            $attempt->get_provider()
                !== Provider::PAYPAL
        ) {
            throw new \moodle_exception(
                'commerce_paypal_reconciliation_wrong_provider',
                'local_subscriptions'
            );
        }

        $paymentid =
            (int)$attempt->get_id();

        $purchase =
            $this->database->get_record(
                CommercePersistenceSchema::TABLE_PURCHASE,
                [
                    'purchaseuuid' =>
                        $attempt->get_purchase_uuid(),
                ],
                '*',
                MUST_EXIST
            );

        $orderid = trim(
            (string)(
                $attempt->get_provider_reference()
                ?? $attempt->get_provider_order_id()
                ?? ''
            )
        );

        if ($orderid === '') {
            throw new \moodle_exception(
                'commerce_paypal_reconciliation_missing_order',
                'local_subscriptions'
            );
        }

        $provider =
            $this->probe(
                $attempt,
                $orderid
            );

        $amountmatches =
            $provider->amountminor !== null
            && $provider->amountminor
                === $attempt->get_amount_minor()
            && $provider->amountminor
                === (int)$purchase->totalminor;

        $currencymatches =
            $provider->currency !== null
            && $provider->currency
                === $attempt->get_currency()
            && $provider->currency
                === strtoupper(
                    (string)$purchase->currency
                );

        $campusrefundedminor = 0;
        foreach (
            $this->refunds->find_for_payment(
                $paymentid
            )
            as $refund
        ) {
            if (
                $refund
                    ->is_counted_against_refundable_amount()
            ) {
                $campusrefundedminor +=
                    $refund->get_amount_minor();
            }
        }

        $refundmatches =
            $campusrefundedminor
                === $provider->refundedminor;

        $providerpaid =
            $provider
                ->is_financially_settled();

        $alreadycomplete =
            in_array(
                $attempt->get_status(),
                [
                    CommercePaymentAttemptStatus::PAID,
                    CommercePaymentAttemptStatus::COMPLETED,
                ],
                true
            )
            && (string)$purchase->status
                === CommercePurchaseStatus::FULFILLED;

        $blockers = [];

        if (!$providerpaid) {
            $blockers[] =
                'provider_not_paid';
        }

        if (!$amountmatches) {
            $blockers[] =
                'amount_mismatch';
        }

        if (!$currencymatches) {
            $blockers[] =
                'currency_mismatch';
        }

        // Never repair an unfinished Campus payment from a provider state
        // that is already partially or fully refunded. That situation needs
        // human review, not automatic fulfillment.
        if (
            !$alreadycomplete
            && $provider->is_refunded_state()
        ) {
            $blockers[] =
                'provider_already_refunded';
        }

        if (
            !$alreadycomplete
            && !$provider
                ->has_completed_capture()
        ) {
            $blockers[] =
                'capture_not_completed';
        }

        if (
            in_array(
                $attempt->get_status(),
                [
                    CommercePaymentAttemptStatus::REFUNDED,
                    CommercePaymentAttemptStatus::CANCELLED,
                    CommercePaymentAttemptStatus::FAILED,
                    CommercePaymentAttemptStatus::ERROR,
                ],
                true
            )
        ) {
            $blockers[] =
                'campus_payment_terminal_'
                . $attempt->get_status();
        }

        return new PayPalPaymentReconciliationInspection(
            $paymentid,
            (int)$purchase->id,
            (string)$purchase->reference,
            (string)$purchase->purchaseuuid,
            $attempt->get_status(),
            (string)$purchase->status,
            $attempt->get_amount_minor(),
            $attempt->get_currency(),
            $campusrefundedminor,
            $orderid,
            $provider,
            $amountmatches,
            $currencymatches,
            $providerpaid,
            $refundmatches,
            !$alreadycomplete
                && $blockers === [],
            $alreadycomplete,
            $blockers
        );
    }


    private function live_refunded_total_minor(
        int $paymentid,
        string $expectedcurrency
    ): int {
        $total = 0;

        foreach (
            $this->refunds->find_for_payment(
                $paymentid
            )
            as $refund
        ) {
            $providerrefundid = trim(
                (string)$refund
                    ->get_provider_refund_id()
            );

            if ($providerrefundid === '') {
                continue;
            }

            try {
                $live =
                    $this->gateway
                        ->retrieve_refund(
                            $providerrefundid
                        );
            } catch (\Throwable) {
                // A transient provider read error must not invent a total.
                // The page will show a mismatch and invite a re-check.
                continue;
            }

            if (
                $live->get_status()
                    !== 'COMPLETED'
            ) {
                continue;
            }

            if (
                $expectedcurrency !== ''
                && $live->get_currency()
                    !== $expectedcurrency
            ) {
                continue;
            }

            $total +=
                $live->get_amount_minor();
        }

        return $total;
    }

    private function probe(
        CommercePaymentAttempt $attempt,
        string $orderid
    ): PayPalPaymentProviderStatus {
        $order =
            $this->gateway
                ->retrieve_order(
                    $orderid
                );

        $orderpayload =
            $order->get_metadata()[
                'paypal_order'
            ] ?? [];

        if (!is_array($orderpayload)) {
            $orderpayload = [];
        }

        $capture =
            $orderpayload['purchase_units'][0]
                ['payments']['captures'][0]
            ?? null;

        $capturepayload =
            is_array($capture)
                ? $capture
                : [];

        $captureid = trim(
            (string)(
                $capturepayload['id']
                ?? $attempt->get_transaction_id()
                ?? ''
            )
        );

        if (
            $captureid !== ''
            && (
                $capturepayload === []
                || !isset(
                    $capturepayload[
                        'seller_receivable_breakdown'
                    ]
                )
            )
        ) {
            $capturepayload =
                $this->gateway
                    ->retrieve_capture(
                        $captureid
                    );
        }

        $capturestatus = trim(
            (string)(
                $capturepayload['status']
                ?? ''
            )
        );

        $currency = strtoupper(
            trim(
                (string)(
                    $capturepayload['amount']
                        ['currency_code']
                    ?? ''
                )
            )
        );

        $amountminor = null;
        $amountvalue = trim(
            (string)(
                $capturepayload['amount']['value']
                ?? ''
            )
        );

        if (
            $currency !== ''
            && $amountvalue !== ''
        ) {
            $amountminor =
                CommerceCurrencyAmount::from_major_input(
                    $amountvalue,
                    $currency
                )->get_amount_minor();
        }

        $refundedminor =
            $this->live_refunded_total_minor(
                (int)$attempt->get_id(),
                $currency
            );

        $netminor =
            $amountminor === null
                ? null
                : max(
                    0,
                    $amountminor
                        - $refundedminor
                );

        return new PayPalPaymentProviderStatus(
            $orderid,
            (new PayPalGatewayConfiguration())
                ->get_environment(),
            $order->get_status(),
            $captureid !== ''
                ? $captureid
                : null,
            $capturestatus !== ''
                ? $capturestatus
                : null,
            $amountminor,
            $currency !== ''
                ? $currency
                : null,
            $refundedminor,
            $netminor,
            $orderpayload,
            $capturepayload
        );
    }
}
