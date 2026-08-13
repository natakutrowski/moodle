<?php

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentProviderStatus;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationBatchService;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationEngineInterface;
use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationInspection;
use local_subscriptions\payment\dto\InternalEvent;

final class local_subscriptions_commerce_alfa_payment_reconciliation_cron_m8c_test extends advanced_testcase {
    public function test_batch_finds_stale_alfa_payment_and_reconciles_safe_provider_deposit(): void {
        global $DB;
        $this->resetAfterTest(true);

        $purchaseid = $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => bin2hex(random_bytes(16)),
            'reference' => 'cmp_m8c_' . bin2hex(random_bytes(6)),
            'type' => 'digital',
            'userid' => 0,
            'customeremail' => 'm8c@example.test',
            'customerjson' => '{}',
            'currency' => 'RUB',
            'subtotalminor' => 99000,
            'discountminor' => 0,
            'totalminor' => 99000,
            'status' => 'payment_pending',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => time() - 3600,
            'timemodified' => time() - 3600,
        ]);
        $purchase = $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $purchaseid], '*', MUST_EXIST);
        $paymentid = $DB->insert_record('local_subscriptions_commerce_payment', (object)[
            'paymentuuid' => bin2hex(random_bytes(16)),
            'purchaseid' => $purchaseid,
            'sequence' => 0,
            'provider' => 'alfa',
            'status' => 'redirected',
            'amountminor' => 99000,
            'currency' => 'RUB',
            'providerreference' => 'order-m8c',
            'providerorderid' => 'order-m8c',
            'metadatajson' => '{}',
            'providerpayload' => '{}',
            'timecreated' => time() - 3600,
            'timemodified' => time() - 3600,
        ]);

        $engine = new class($paymentid, $purchase) implements AlfaPaymentReconciliationEngineInterface {
            public int $reconciliations = 0;
            public function __construct(private int $paymentid, private object $purchase) {}
            public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection {
                return $this->inspection(false);
            }
            public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection {
                $this->reconciliations++;
                return $this->inspection(true);
            }
            private function inspection(bool $complete): AlfaPaymentReconciliationInspection {
                $event = new InternalEvent('checkout_completed');
                $provider = new AlfaPaymentProviderStatus(
                    'order-m8c', 2, 99000, 'RUB', 'DEPOSITED', 99000, 99000, 0,
                    'commerce-m8c', null, [], $event
                );
                return new AlfaPaymentReconciliationInspection(
                    $this->paymentid,
                    (int)$this->purchase->id,
                    (string)$this->purchase->reference,
                    (string)$this->purchase->purchaseuuid,
                    $complete ? 'paid' : 'redirected',
                    $complete ? 'fulfilled' : 'payment_pending',
                    99000,
                    'RUB',
                    'order-m8c',
                    $provider,
                    true, true, true, true, true,
                    !$complete,
                    $complete,
                    []
                );
            }
        };

        $this->expectOutputRegex(
            '/\[Alfa reconciliation\] RECONCILED cmp_m8c_[a-f0-9]+ payment=\d+ order=order-m8c/'
        );

        $result = (new AlfaPaymentReconciliationBatchService($DB, $engine))->run(20, 300, 172800);
        self::assertSame(1, $result['candidates']);
        self::assertSame(1, $result['checked']);
        self::assertSame(1, $result['reconciled']);
        self::assertSame(1, $engine->reconciliations);
    }

    public function test_batch_ignores_recent_and_non_alfa_payments(): void {
        global $DB;
        $this->resetAfterTest(true);
        $engine = new class implements AlfaPaymentReconciliationEngineInterface {
            public int $inspections = 0;
            public function inspect_payment(int $paymentid): AlfaPaymentReconciliationInspection {
                $this->inspections++;
                throw new RuntimeException('Should not inspect excluded payments.');
            }
            public function reconcile_payment(int $paymentid): AlfaPaymentReconciliationInspection {
                throw new RuntimeException('Should not reconcile excluded payments.');
            }
        };
        $result = (new AlfaPaymentReconciliationBatchService($DB, $engine))->run(20, 300, 172800);
        self::assertSame(0, $result['candidates']);
        self::assertSame(0, $engine->inspections);
    }
}