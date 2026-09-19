<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\creditnote;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRepository;
use moodle_database;

/**
 * Reconciles already-persisted successful refunds with missing credit notes.
 *
 * This is intentionally provider-independent. It supports historical refunds
 * that existed before I7 as well as provider webhook/import paths.
 */
final class CommerceCreditNoteReconciliationService {
    public function __construct(
        private readonly moodle_database $database,
        private readonly ?CommercePaymentRefundRepository $refunds = null,
        private readonly ?CommerceCreditNoteIssuer $issuer = null
    ) {}

    public function reconcile_payment(int $paymentid): int {
        if ($paymentid <= 0) {
            return 0;
        }

        $refunds = $this->refunds
            ?? new CommercePaymentRefundRepository($this->database);
        $issuer = $this->issuer
            ?? new CommerceCreditNoteIssuer($this->database);

        $issued = 0;
        foreach ($refunds->find_for_payment($paymentid) as $refund) {
            if ($issuer->find_by_refund_id($refund->get_id()) !== null) {
                continue;
            }

            if ($issuer->issue_if_eligible($refund) !== null) {
                $issued++;
            }
        }

        return $issued;
    }
}
