<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;

defined('MOODLE_INTERNAL') || die();

/**
 * Native idempotent refund command.
 */
final class CommercePaymentRefundCommand {
    public function __construct(
        private readonly CommercePaymentRepository $payments,
        private readonly CommercePaymentRefundRepository $refunds,
        private readonly CommercePaymentRefundService $service,
        private readonly ?CommerceCreditNoteIssuer $creditnotes = null
    ) {}

    public function execute(
        int $paymentid,
        int $amountminor,
        string $idempotencykey,
        ?string $reason = null,
        ?int $createdby = null,
        array $metadata = []
    ): CommercePaymentRefundRecord {
        $idempotencykey = trim($idempotencykey);

        if ($idempotencykey === '') {
            throw new CommercePaymentRefundException(
                'A Commerce refund requires an idempotency key.',
                'refund_idempotency_required'
            );
        }

        $existing = $this->refunds->find_by_idempotency_key(
            $idempotencykey
        );

        if ($existing !== null) {
            if (
                $existing->get_payment_id() !== $paymentid
                || $existing->get_amount_minor() !== $amountminor
            ) {
                throw new CommercePaymentRefundException(
                    'The refund idempotency key is already used by another command.',
                    'refund_idempotency_conflict',
                    $existing->get_provider()
                );
            }

            $this->credit_note_issuer()->issue_if_eligible($existing);
            return $existing;
        }

        $payment = $this->payments->find($paymentid);
        if ($payment === null) {
            throw new CommercePaymentRefundException(
                'Unknown Commerce payment.',
                'refund_payment_unknown'
            );
        }

        if (!in_array(
            $payment->get_status(),
            [
                CommercePaymentAttemptStatus::PAID,
                CommercePaymentAttemptStatus::COMPLETED,
            ],
            true
        )) {
            throw new CommercePaymentRefundException(
                'Only a paid Commerce payment can be refunded.',
                'refund_payment_not_paid',
                $payment->get_provider()
            );
        }

        $remaining = $this->refunds->refundable_amount_minor(
            $paymentid,
            $payment->get_amount_minor()
        );

        if ($amountminor <= 0 || $amountminor > $remaining) {
            throw new CommercePaymentRefundException(
                'The requested refund exceeds the refundable amount.',
                'refund_amount_invalid',
                $payment->get_provider(),
                [
                    'requested' => $amountminor,
                    'remaining' => $remaining,
                ]
            );
        }

        $providerpaymentid =
            $payment->get_transaction_id()
            ?? $payment->get_provider_reference()
            ?? $payment->get_provider_order_id();

        if ($providerpaymentid === null) {
            throw new CommercePaymentRefundException(
                'The Commerce payment has no provider identifier.',
                'refund_provider_payment_id_missing',
                $payment->get_provider()
            );
        }

        $record = $this->refunds->create_pending(
            $paymentid,
            $payment->get_provider(),
            $idempotencykey,
            $payment->get_currency(),
            $amountminor,
            $reason,
            array_merge(
                $metadata,
                [
                    'purchaseuuid' => $payment->get_purchase_uuid(),
                    'providerpaymentid' => $providerpaymentid,
                ]
            ),
            $createdby
        );

        try {
            $result = $this->service->refund(
                $payment->get_provider(),
                new CommercePaymentRefundRequest(
                    'PAYMENT-' . $paymentid,
                    $providerpaymentid,
                    $payment->get_currency(),
                    $amountminor,
                    $reason,
                    array_merge(
                        $metadata,
                        [
                            'purchase_reference' =>
                                $payment->get_purchase_uuid(),
                        ]
                    )
                ),
                new CommercePaymentProviderContext(
                    $idempotencykey,
                    true,
                    [
                        'operation' => 'refund',
                        'paymentid' => $paymentid,
                    ]
                )
            );

            $completed = $this->refunds->complete(
                $record->get_id(),
                $result
            );
        } catch (\Throwable $exception) {
            $this->refunds->mark_failed(
                $record->get_id(),
                [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        // Document issuance happens after the provider/refund ledger transaction.
        // If it fails, the refund remains correctly persisted as succeeded and
        // an idempotent retry can issue the missing credit note.
        $this->credit_note_issuer()->issue_if_eligible($completed);

        // Refunds are financial-only. Rights are deliberately left untouched
        // until an administrator explicitly invokes purchase-rights revocation.
        return $completed;
    }


    private function credit_note_issuer(): CommerceCreditNoteIssuer {
        if ($this->creditnotes !== null) {
            return $this->creditnotes;
        }

        global $DB;
        return new CommerceCreditNoteIssuer($DB);
    }
}
