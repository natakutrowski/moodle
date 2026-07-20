<?php

namespace local_subscriptions\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\services\DigitalPaymentReconciliationService;

/**
 * Backward-compatible facade for digital payment reconciliation.
 *
 * New business logic must live in DigitalPaymentReconciliationService.
 */
final class digital_payment_reconciler {

    public static function reconcile_pending(
        array $options = []
    ): array {
        return
            (new DigitalPaymentReconciliationService())
                ->reconcile_pending(
                    $options
                );
    }

    public static function check_provider_status(
        \stdClass $paymentrequest
    ): array {
        return
            (new DigitalPaymentReconciliationService())
                ->check_provider_status(
                    $paymentrequest
                );
    }

    public static function check_one(
        int $purchaseid
    ): array {
        return
            (new DigitalPaymentReconciliationService())
                ->check_one(
                    $purchaseid
                );
    }
}