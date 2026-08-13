<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationEngineInterface;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationInspection;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaReturnPollingResult;
use local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaReturnPollingService;
use local_subscriptions\payment\dto\InternalEvent;

/** @covers \local_subscriptions\commerce\payment\reconciliation\alfa\returnflow\AlfaReturnPollingService */
final class commerce_alfa_active_confirmation_m8e1_test extends advanced_testcase {
    public function test_one_browser_poll_reconciles_a_newly_deposited_payment(): void {
        $before = $this->inspection(true, false, []);
        $after = $this->inspection(false, true, []);
        $engine = new m8e1_engine($before, $after);

        $result = (new AlfaReturnPollingService($engine))->check(565);

        self::assertSame(AlfaReturnPollingResult::COMPLETE, $result->status);
        self::assertSame(1, $engine->inspectcalls);
        self::assertSame(1, $engine->reconcilecalls);
        self::assertTrue($result->inspection->alreadycomplete);
    }

    public function test_provider_not_yet_paid_stays_pending_without_mutation(): void {
        $pending = $this->inspection(false, false, ['provider_not_paid']);
        $engine = new m8e1_engine($pending, $pending);

        $result = (new AlfaReturnPollingService($engine))->check(565);

        self::assertSame(AlfaReturnPollingResult::PENDING, $result->status);
        self::assertSame(0, $engine->reconcilecalls);
    }

    public function test_safety_blocker_returns_unsafe_without_mutation(): void {
        $blocked = $this->inspection(false, false, ['amount_mismatch']);
        $engine = new m8e1_engine($blocked, $blocked);

        $result = (new AlfaReturnPollingService($engine))->check(565);

        self::assertSame(AlfaReturnPollingResult::UNSAFE, $result->status);
        self::assertSame(0, $engine->reconcilecalls);
    }

    public function test_order_result_contains_active_alfa_splash_and_polling_module(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            'data-alfa-payment-confirmation',
            $source
        );
        self::assertStringContainsString(
            'local_subscriptions/alfa_payment_confirmation',
            $source
        );
        self::assertStringContainsString(
            '/local/subscriptions/payment/alfa_return_poll.php',
            $source
        );
        self::assertStringContainsString(
            "strtolower((string)\$order->provider) === 'alfa'",
            $source
        );
    }

    public function test_poll_endpoint_requires_post_sesskey_and_order_ownership(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/alfa_return_poll.php'
        );

        self::assertStringContainsString("REQUEST_METHOD", $source);
        self::assertStringContainsString('require_sesskey()', $source);
        self::assertStringContainsString('find_for_user', $source);
        self::assertStringContainsString('get_purchase_uuid()', $source);
        self::assertStringContainsString('Provider::ALFA', $source);
    }

    private function inspection(
        bool $reconcilable,
        bool $complete,
        array $blockers
    ): AlfaPaymentReconciliationInspection {
        $paid = $reconcilable || $complete;
        $provider = new AlfaPaymentProviderStatus(
            '03805f1e-a41f-752f-bab0-6b1702570b20',
            $paid ? 2 : 0,
            1000000,
            'RUB',
            $paid ? 'DEPOSITED' : 'REGISTERED',
            $paid ? 1000000 : null,
            $paid ? 1000000 : null,
            0,
            'm8e1-test',
            $paid ? 'Deposited' : 'Registered',
            [],
            new InternalEvent(
                $paid ? 'checkout_completed' : 'payment_failed',
                [
                    'amount_minor' => 1000000,
                    'currency' => 'RUB',
                    'meta' => [
                        'provider' => 'alfa',
                        'session' => '03805f1e-a41f-752f-bab0-6b1702570b20',
                    ],
                ]
            )
        );

        return new AlfaPaymentReconciliationInspection(
            565,
            1,
            'cmp_777e41fe34d040bb228503a6',
            'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            $complete ? 'paid' : 'redirected',
            $complete ? 'fulfilled' : 'payment_pending',
            1000000,
            'RUB',
            '03805f1e-a41f-752f-bab0-6b1702570b20',
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

final class m8e1_engine implements AlfaPaymentReconciliationEngineInterface {
    public int $inspectcalls = 0;
    public int $reconcilecalls = 0;

    public function __construct(
        private readonly AlfaPaymentReconciliationInspection $before,
        private readonly AlfaPaymentReconciliationInspection $after
    ) {
    }

    public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $this->inspectcalls++;
        return $this->before;
    }

    public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection {
        $this->reconcilecalls++;
        return $this->after;
    }
}
