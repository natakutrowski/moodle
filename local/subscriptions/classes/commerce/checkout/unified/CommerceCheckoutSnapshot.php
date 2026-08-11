<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\unified;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\CommercePaymentRequest;
use local_subscriptions\commerce\purchase\CommercePurchaseRequest;

/** Transaction snapshot produced immediately before provider initialization. */
final class CommerceCheckoutSnapshot {
    public function __construct(
        private readonly CommerceCheckoutSummary $summary,
        private readonly CommercePurchaseRequest $purchase,
        private readonly CommercePaymentRequest $paymentrequest
    ) {
        if ($summary->get_total_minor() !== $purchase->get_total_amount_minor()
            || $purchase->get_total_amount_minor() !== $paymentrequest->get_amount_minor()) {
            throw new \coding_exception('Checkout, purchase and payment totals must be identical.');
        }
    }

    public function get_summary(): CommerceCheckoutSummary { return $this->summary; }
    public function get_purchase_request(): CommercePurchaseRequest { return $this->purchase; }
    public function get_payment_request(): CommercePaymentRequest { return $this->paymentrequest; }
    public function get_total_minor(): int { return $this->summary->get_total_minor(); }
    public function get_currency(): string { return $this->summary->get_currency(); }
}
