<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderContext;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteIssuer;
use local_subscriptions\commerce\order\creditnote\CommerceCreditNoteReconciliationService;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentRefundImportService {
    public function __construct(
        private readonly CommercePaymentRepository $payments,
        private readonly CommercePaymentRefundRepository $refunds,
        private readonly CommercePaymentProviderRegistry $providers,
        private readonly ?CommerceCreditNoteIssuer $creditnotes = null
    ) {}

    public function import_for_payment(
        int $paymentid,
        ?int $createdby = null
    ): int {
        $payment = $this->payments->find($paymentid);
        if ($payment === null) {
            throw new \coding_exception('Unknown Commerce payment.');
        }

        $provider = null;
        foreach ($this->providers->all() as $candidate) {
            if ($candidate->get_key() === $payment->get_provider()) {
                $provider = $candidate;
                break;
            }
        }

        if (!$provider instanceof CommerceRefundHistoryCapablePaymentProvider) {
            return 0;
        }

        $providerpaymentid =
            $payment->get_transaction_id()
            ?? $payment->get_provider_reference()
            ?? $payment->get_provider_order_id();

        if ($providerpaymentid === null) {
            return 0;
        }

        $results = $provider->list_refunds(
            $providerpaymentid,
            $payment->get_currency(),
            new CommercePaymentProviderContext(
                'refund-import-' . $paymentid . '-' . time(),
                true,
                ['operation' => 'refund_history_import']
            )
        );

        $imported = 0;
        foreach ($results as $result) {
            if (
                !empty(
                    $result
                        ->get_metadata()['aggregate_total']
                )
            ) {
                $knownminor = 0;

                foreach (
                    $this->refunds->find_for_payment(
                        $paymentid
                    )
                    as $knownrefund
                ) {
                    $payload =
                        $knownrefund->get_provider_payload()
                        ?? [];

                    if (
                        !empty(
                            $payload['aggregate_total']
                        )
                    ) {
                        continue;
                    }

                    if (
                        $knownrefund
                            ->is_counted_against_refundable_amount()
                    ) {
                        $knownminor +=
                            $knownrefund
                                ->get_amount_minor();
                    }
                }

                $missingminor = max(
                    0,
                    $result->get_amount_minor()
                        - $knownminor
                );

                if ($missingminor <= 0) {
                    continue;
                }

                $result =
                    new CommercePaymentRefundResult(
                        $result->get_provider_key(),
                        $result->get_provider_refund_id(),
                        $result->get_status(),
                        $result->get_currency(),
                        $missingminor,
                        array_merge(
                            $result->get_metadata(),
                            [
                                'provider_total_minor' =>
                                    $result->get_amount_minor(),
                                'known_individual_minor' =>
                                    $knownminor,
                            ]
                        )
                    );
            }

            $existing =
                $this->refunds->find_by_provider_refund_id(
                    $result->get_provider_key(),
                    $result->get_provider_refund_id()
                );

            $beforeamount = $existing?->get_amount_minor();
            $beforestatus = $existing?->get_status();

            $synchronized =
                $this->refunds->import_provider_refund(
                    $paymentid,
                    $result,
                    $createdby,
                    [
                        'purchaseuuid' =>
                            $payment->get_purchase_uuid(),
                        'providerpaymentid' =>
                            $providerpaymentid,
                    ]
                );

            $this->credit_note_issuer()
                ->issue_if_eligible($synchronized);

            if (
                $existing === null
                || $beforeamount !==
                    $synchronized->get_amount_minor()
                || $beforestatus !==
                    $synchronized->get_status()
            ) {
                $imported++;
            }
        }

        // Also reconcile successful refunds that were already persisted before
        // I7 document issuance existed. This is especially important for
        // PayPal, whose history adapter deliberately returns no synthetic list.
        (new CommerceCreditNoteReconciliationService(
            $this->payments_database(),
            $this->refunds,
            $this->credit_note_issuer()
        ))->reconcile_payment($paymentid);

        // Provider-side refund imports are financial synchronization only.
        // They never revoke customer rights automatically.
        return $imported;
    }

    private function payments_database(): \moodle_database {
        global $DB;
        return $DB;
    }

    private function credit_note_issuer(): CommerceCreditNoteIssuer {
        if ($this->creditnotes !== null) {
            return $this->creditnotes;
        }

        global $DB;
        return new CommerceCreditNoteIssuer($DB);
    }
}
