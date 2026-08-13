<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa;

defined('MOODLE_INTERNAL') || die();

/** Stable contract shared by manual, CRM and scheduled Alfa reconciliation. */
interface AlfaPaymentReconciliationEngineInterface {
    public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection;
    public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection;
}
