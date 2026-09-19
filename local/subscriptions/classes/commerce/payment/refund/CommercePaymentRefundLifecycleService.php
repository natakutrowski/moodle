<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\refund;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;

/**
 * Legacy compatibility helper for refund aggregate reconciliation.
 *
 * Since 7.97 M4.6.2, refunds are strictly financial. This class deliberately
 * performs no rights, enrolment, digital-library or pedagogical mutation.
 * Explicit rights removal belongs to CommercePurchaseRightsRevocationService.
 *
 * @deprecated M4.6.2. New code must not use refund lifecycle reconciliation
 *             to mutate customer rights.
 */
final class CommercePaymentRefundLifecycleService {
    public function __construct(
        private readonly CommercePaymentRepository $payments,
        private readonly CommercePaymentRefundRepository $refunds
    ) {
    }

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;

        return new self(
            new CommercePaymentRepository($db),
            new CommercePaymentRefundRepository($db)
        );
    }

    /**
     * Report whether successful refunds now cover the full payment amount.
     *
     * The method name is retained only for compatibility with older call sites.
     * It has no lifecycle side effects and must never revoke rights.
     */
    public function reconcile_full_refund(int $paymentid, ?int $now = null): bool {
        $payment = $this->payments->find($paymentid);
        if ($payment === null) {
            throw new \coding_exception('Unknown Commerce payment for refund reconciliation.');
        }

        return $this->refunds->successful_refunded_amount_minor($paymentid)
            >= $payment->get_amount_minor();
    }
}
