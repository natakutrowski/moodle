<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\alfa\returnflow;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationEngineInterface;

/**
 * Gives Alfa browser returns a bounded synchronous chance to finish Commerce.
 *
 * The service never asserts payment itself. Every attempt uses the M8A engine,
 * which queries Alfa authoritatively and validates amount/currency/deposit state.
 */
final class AlfaInstantReturnReconciliationService {
    /** Retry after 0 ms, 500 ms and 1000 ms (1.5 s maximum waiting). */
    private const RETRY_DELAYS_US = [0, 500000, 1000000];

    public function __construct(
        private readonly AlfaPaymentReconciliationEngineInterface $engine,
        private readonly AlfaInstantReturnSleeperInterface $sleeper
    ) {
    }

    public function reconcile(int $paymentid): AlfaInstantReturnResult {
        $last = null;

        foreach (self::RETRY_DELAYS_US as $index => $delay) {
            if ($delay > 0) {
                $this->sleeper->sleep_microseconds($delay);
            }

            $inspection = $this->engine->inspect_payment($paymentid);
            $last = $inspection;
            $attempts = $index + 1;

            if ($inspection->alreadycomplete) {
                return new AlfaInstantReturnResult(
                    AlfaInstantReturnResult::COMPLETE,
                    $inspection,
                    $attempts
                );
            }

            if ($inspection->reconcilable) {
                $after = $this->engine->reconcile_payment($paymentid);
                return new AlfaInstantReturnResult(
                    $after->alreadycomplete
                        ? AlfaInstantReturnResult::COMPLETE
                        : AlfaInstantReturnResult::PENDING,
                    $after,
                    $attempts
                );
            }

            if (!$this->is_provider_pending_only($inspection->blockers)) {
                return new AlfaInstantReturnResult(
                    AlfaInstantReturnResult::UNSAFE,
                    $inspection,
                    $attempts
                );
            }
        }

        return new AlfaInstantReturnResult(
            AlfaInstantReturnResult::PENDING,
            $last,
            count(self::RETRY_DELAYS_US)
        );
    }

    private function is_provider_pending_only(array $blockers): bool {
        return $blockers === ['provider_not_paid'];
    }
}
