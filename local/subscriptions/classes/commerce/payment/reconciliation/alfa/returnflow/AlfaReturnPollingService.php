<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationEngineInterface;

/**
 * Performs one safe live Alfa check for the active browser-return splash.
 *
 * No browser-supplied status is trusted. M8A re-queries Alfa and verifies
 * amount, currency and deposited state before any Commerce mutation.
 */
final class AlfaReturnPollingService {
    public function __construct(
        private readonly AlfaPaymentReconciliationEngineInterface $engine
    ) {
    }

    public function check(int $paymentid): AlfaReturnPollingResult {
        $inspection = $this->engine->inspect_payment($paymentid);

        if ($inspection->alreadycomplete) {
            return new AlfaReturnPollingResult(
                AlfaReturnPollingResult::COMPLETE,
                $inspection
            );
        }

        if ($inspection->reconcilable) {
            $after = $this->engine->reconcile_payment($paymentid);

            return new AlfaReturnPollingResult(
                $after->alreadycomplete
                    ? AlfaReturnPollingResult::COMPLETE
                    : AlfaReturnPollingResult::PENDING,
                $after
            );
        }

        if ($inspection->blockers === ['provider_not_paid']) {
            return new AlfaReturnPollingResult(
                AlfaReturnPollingResult::PENDING,
                $inspection
            );
        }

        return new AlfaReturnPollingResult(
            AlfaReturnPollingResult::UNSAFE,
            $inspection
        );
    }
}
