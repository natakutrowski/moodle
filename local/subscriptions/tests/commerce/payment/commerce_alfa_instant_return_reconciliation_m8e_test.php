<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationEngineInterface;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationInspection;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnResult;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnSleeperInterface;
use local_subscriptions\payment\dto\InternalEvent;

/** @covers \local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaInstantReturnReconciliationService */
final class commerce_alfa_instant_return_reconciliation_m8e_test extends advanced_testcase {
    public function test_immediately_deposited_payment_is_reconciled_before_return(): void {
        $engine = new m8e_fake_engine([
            $this->inspection(true, false, []),
        ], $this->inspection(false, true, []));
        $sleeper = new m8e_fake_sleeper();

        $result = (new AlfaInstantReturnReconciliationService($engine, $sleeper))->reconcile(42);

        self::assertSame(AlfaInstantReturnResult::COMPLETE, $result->status);
        self::assertSame(1, $result->attempts);
        self::assertSame(1, $engine->inspectcalls);
        self::assertSame(1, $engine->reconcilecalls);
        self::assertSame([], $sleeper->delays);
    }

    public function test_provider_propagation_gets_two_short_retries_then_reconciles(): void {
        $engine = new m8e_fake_engine([
            $this->inspection(false, false, ['provider_not_paid']),
            $this->inspection(false, false, ['provider_not_paid']),
            $this->inspection(true, false, []),
        ], $this->inspection(false, true, []));
        $sleeper = new m8e_fake_sleeper();

        $result = (new AlfaInstantReturnReconciliationService($engine, $sleeper))->reconcile(43);

        self::assertSame(AlfaInstantReturnResult::COMPLETE, $result->status);
        self::assertSame(3, $result->attempts);
        self::assertSame([500000, 1000000], $sleeper->delays);
        self::assertSame(1, $engine->reconcilecalls);
    }

    public function test_still_pending_after_retries_does_not_mutate_payment(): void {
        $pending = $this->inspection(false, false, ['provider_not_paid']);
        $engine = new m8e_fake_engine([$pending, $pending, $pending], $pending);
        $sleeper = new m8e_fake_sleeper();

        $result = (new AlfaInstantReturnReconciliationService($engine, $sleeper))->reconcile(44);

        self::assertSame(AlfaInstantReturnResult::PENDING, $result->status);
        self::assertSame(3, $result->attempts);
        self::assertSame(0, $engine->reconcilecalls);
        self::assertSame([500000, 1000000], $sleeper->delays);
    }

    public function test_safety_mismatch_is_not_retried_or_finalized(): void {
        $engine = new m8e_fake_engine([
            $this->inspection(false, false, ['amount_mismatch']),
        ], $this->inspection(false, false, ['amount_mismatch']));
        $sleeper = new m8e_fake_sleeper();

        $result = (new AlfaInstantReturnReconciliationService($engine, $sleeper))->reconcile(45);

        self::assertSame(AlfaInstantReturnResult::UNSAFE, $result->status);
        self::assertSame(1, $result->attempts);
        self::assertSame(0, $engine->reconcilecalls);
        self::assertSame([], $sleeper->delays);
    }

    public function test_already_complete_payment_is_idempotent(): void {
        $complete = $this->inspection(false, true, []);
        $engine = new m8e_fake_engine([$complete], $complete);
        $sleeper = new m8e_fake_sleeper();

        $result = (new AlfaInstantReturnReconciliationService($engine, $sleeper))->reconcile(46);

        self::assertSame(AlfaInstantReturnResult::COMPLETE, $result->status);
        self::assertSame(0, $engine->reconcilecalls);
    }

    public function test_return_endpoint_no_longer_marks_alfa_failed_on_transient_error(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/return.php'
        );

        self::assertStringContainsString('AlfaInstantReturnReconciliationService', $source);
        self::assertStringContainsString("'temporary_error'", $source);

        $alfablock = substr(
            $source,
            strpos($source, 'if ($provider === Provider::ALFA)'),
            strpos($source, '/* Stripe fulfillment', strpos($source, 'if ($provider === Provider::ALFA)'))
                - strpos($source, 'if ($provider === Provider::ALFA)')
        );
        self::assertStringNotContainsString(
            'CommercePaymentAttemptStatus::FAILED',
            $alfablock
        );
    }

    private function inspection(
        bool $reconcilable,
        bool $complete,
        array $blockers
    ): AlfaPaymentReconciliationInspection {
        $paid = $reconcilable || $complete;
        $event = new InternalEvent(
            $paid ? 'checkout_completed' : 'payment_failed',
            [
                'amount_minor' => 99000,
                'currency' => 'RUB',
                'meta' => ['provider' => 'alfa', 'session' => 'm8e-order'],
            ]
        );
        $provider = new AlfaPaymentProviderStatus(
            'm8e-order',
            $paid ? 2 : 0,
            99000,
            'RUB',
            $paid ? 'DEPOSITED' : 'REGISTERED',
            $paid ? 99000 : null,
            $paid ? 99000 : null,
            0,
            'm8e-order-number',
            $paid ? 'Deposited' : 'Registered',
            [],
            $event
        );

        return new AlfaPaymentReconciliationInspection(
            42,
            7,
            'cmp_m8e_test',
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            $complete ? 'paid' : 'redirected',
            $complete ? 'fulfilled' : 'payment_pending',
            99000,
            'RUB',
            'm8e-order',
            $provider,
            true,
            true,
            true,
            true,
            $paid,
            $reconcilable,
            $complete,
            $blockers
        );
    }
}

final class m8e_fake_engine implements AlfaPaymentReconciliationEngineInterface {
    public int $inspectcalls = 0;
    public int $reconcilecalls = 0;

    /** @param AlfaPaymentReconciliationInspection[] $inspections */
    public function __construct(
        private array $inspections,
        private readonly AlfaPaymentReconciliationInspection $after
    ) {
    }

    public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $this->inspectcalls++;
        if (!$this->inspections) {
            throw new \RuntimeException('Unexpected additional M8E inspection.');
        }
        return array_shift($this->inspections);
    }

    public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $this->reconcilecalls++;
        return $this->after;
    }
}

final class m8e_fake_sleeper implements AlfaInstantReturnSleeperInterface {
    public array $delays = [];

    public function sleep_microseconds(int $microseconds): void {
        $this->delays[] = $microseconds;
    }
}
