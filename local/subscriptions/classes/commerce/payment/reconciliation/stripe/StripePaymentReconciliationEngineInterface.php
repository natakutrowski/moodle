<?php
declare(strict_types=1);
namespace local_subscriptions\commerce\payment\reconciliation\stripe;
defined('MOODLE_INTERNAL') || die();
interface StripePaymentReconciliationEngineInterface {
    public function inspect_payment(int $paymentid): StripePaymentReconciliationInspection;
    public function reconcile_payment(int $paymentid): StripePaymentReconciliationInspection;
}
