<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationFinalizerInterface;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentStatusProbeInterface;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\payment\dto\InternalEvent;

/** @covers \local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService */
final class commerce_alfa_payment_reconciliation_m8a_test extends advanced_testcase {
    private const UUID = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const REFERENCE = 'cmp_m8a_000000000000000001';
    private const ORDERID = 'alfa-m8a-order-001';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_paid_deposited_matching_alfa_order_is_reconcilable(): void {
        global $DB;
        $paymentid = $this->seed_redirected_payment();
        $service = $this->service($this->paid_probe(99000), new m8a_noop_finalizer());

        $inspection = $service->inspect_payment($paymentid);

        self::assertTrue($inspection->providerpaid);
        self::assertTrue($inspection->amountmatches);
        self::assertTrue($inspection->currencymatches);
        self::assertTrue($inspection->approvedamountmatches);
        self::assertTrue($inspection->depositedamountmatches);
        self::assertTrue($inspection->reconcilable);
        self::assertSame([], $inspection->blockers);
        self::assertSame(2, $inspection->provider->orderstatus);
        self::assertSame('DEPOSITED', $inspection->provider->paymentstate);
    }

    public function test_amount_mismatch_blocks_reconciliation(): void {
        $paymentid = $this->seed_redirected_payment();
        $service = $this->service($this->paid_probe(199000), new m8a_noop_finalizer());

        $inspection = $service->inspect_payment($paymentid);

        self::assertFalse($inspection->amountmatches);
        self::assertFalse($inspection->depositedamountmatches);
        self::assertFalse($inspection->reconcilable);
        self::assertContains('amount_mismatch', $inspection->blockers);
        self::assertContains('deposited_amount_mismatch', $inspection->blockers);
    }

    public function test_provider_not_paid_is_read_only_and_blocked(): void {
        $paymentid = $this->seed_redirected_payment();
        $probe = new m8a_fake_probe(new AlfaPaymentProviderStatus(
            self::ORDERID,
            0,
            99000,
            'RUB',
            null,
            null,
            null,
            0,
            'commerce-1-1',
            'registered',
            ['orderStatus' => 0, 'amount' => 99000, 'currency' => 810],
            new InternalEvent('payment_failed', [
                'amount_minor' => 99000,
                'currency' => 'RUB',
                'meta' => ['provider' => 'alfa', 'session' => self::ORDERID],
            ])
        ));
        $service = $this->service($probe, new m8a_noop_finalizer());

        $inspection = $service->inspect_payment($paymentid);

        self::assertFalse($inspection->providerpaid);
        self::assertFalse($inspection->reconcilable);
        self::assertContains('provider_not_paid', $inspection->blockers);
    }

    public function test_execute_reuses_finalizer_and_returns_completed_state(): void {
        global $DB;
        $paymentid = $this->seed_redirected_payment();
        $finalizer = new m8a_database_finalizer($DB);
        $service = $this->service($this->paid_probe(99000), $finalizer);

        $after = $service->reconcile_payment($paymentid);

        self::assertTrue($finalizer->called);
        self::assertSame(CommercePaymentAttemptStatus::PAID, $after->campuspaymentstatus);
        self::assertSame('fulfilled', $after->campuspurchasestatus);
        self::assertTrue($after->alreadycomplete);
        self::assertFalse($after->reconcilable);
    }

    private function service(
        AlfaPaymentStatusProbeInterface $probe,
        AlfaPaymentReconciliationFinalizerInterface $finalizer
    ): AlfaPaymentReconciliationService {
        global $DB;
        return new AlfaPaymentReconciliationService(
            $DB,
            new CommercePaymentRepository($DB),
            $probe,
            $finalizer
        );
    }

    private function paid_probe(int $amountminor): AlfaPaymentStatusProbeInterface {
        return new m8a_fake_probe(new AlfaPaymentProviderStatus(
            self::ORDERID,
            2,
            $amountminor,
            'RUB',
            'DEPOSITED',
            $amountminor,
            $amountminor,
            0,
            'commerce-1-1',
            'Успешно',
            [
                'orderStatus' => 2,
                'amount' => $amountminor,
                'currency' => 810,
                'paymentAmountInfo' => [
                    'paymentState' => 'DEPOSITED',
                    'approvedAmount' => $amountminor,
                    'depositedAmount' => $amountminor,
                    'refundedAmount' => 0,
                ],
            ],
            new InternalEvent('checkout_completed', [
                'amount_minor' => $amountminor,
                'currency' => 'RUB',
                'meta' => [
                    'provider' => 'alfa',
                    'session' => self::ORDERID,
                    'orderStatus' => 2,
                ],
            ])
        ));
    }

    private function seed_redirected_payment(): int {
        global $DB;
        $now = time();
        $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => self::UUID,
            'reference' => self::REFERENCE,
            'type' => 'digital',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => null,
            'customeremail' => 'm8a@example.test',
            'status' => 'payment_pending',
            'currency' => 'RUB',
            'subtotalminor' => 99000,
            'discountminor' => 0,
            'totalminor' => 99000,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $repo = new CommercePaymentRepository($DB);
        $attempt = $repo->create(self::UUID, 'alfa', 99000, 'RUB');
        $launched = $repo->record_provider_launch(
            (int)$attempt->get_id(),
            self::ORDERID,
            self::ORDERID,
            'https://securepayecom.example.test/',
            ['provider' => 'alfa']
        );
        return (int)$launched->get_id();
    }
}

final class m8a_fake_probe implements AlfaPaymentStatusProbeInterface {
    public function __construct(private readonly AlfaPaymentProviderStatus $status) {
    }
    public function probe(string $orderid): AlfaPaymentProviderStatus {
        if ($orderid !== $this->status->orderid) {
            throw new \RuntimeException('Unexpected order ID in test probe.');
        }
        return $this->status;
    }
}

final class m8a_noop_finalizer implements AlfaPaymentReconciliationFinalizerInterface {
    public function finalize(InternalEvent $event): void {
    }
}

final class m8a_database_finalizer implements AlfaPaymentReconciliationFinalizerInterface {
    public bool $called = false;
    public function __construct(private readonly \moodle_database $database) {
    }
    public function finalize(InternalEvent $event): void {
        $this->called = true;
        $paymentid = (int)$event->meta['commerce_payment_id'];
        (new CommercePaymentRepository($this->database))->update_status(
            $paymentid,
            CommercePaymentAttemptStatus::PAID,
            (string)($event->meta['session'] ?? ''),
            ['test' => 'm8a'],
            time()
        );
        $this->database->set_field(
            'local_subscriptions_commerce_purchase',
            'status',
            'fulfilled',
            ['purchaseuuid' => (string)$event->meta['commerce_purchase_uuid']]
        );
    }
}
