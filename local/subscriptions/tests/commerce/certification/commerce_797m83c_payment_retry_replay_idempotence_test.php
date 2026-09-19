<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\returnflow\CommercePaymentEventSynchronizer;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

/** M8.3-C: payment retry / callback replay / idempotence certification. */
final class commerce_797m83c_payment_retry_replay_idempotence_test extends advanced_testcase {
    private const PURCHASE_UUID = '83c11111222243338444555555555555';
    private const PURCHASE_REFERENCE = 'cmp_m83c_retry_replay';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->insert_purchase();
    }

    public function test_failed_attempt_can_recover_to_paid_on_provider_success(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(self::PURCHASE_UUID, 'stripe', 400, 'EUR');
        $payments->record_provider_launch(
            (int)$attempt->get_id(),
            'cs_m83c_retry',
            null,
            'https://checkout.stripe.test/m83c'
        );
        $payments->update_status(
            (int)$attempt->get_id(),
            CommercePaymentAttemptStatus::FAILED
        );

        $event = $this->success_event((int)$attempt->get_id(), 'cs_m83c_retry', 'pi_m83c_retry_paid');

        self::assertTrue((new CommercePaymentEventSynchronizer($payments))->synchronize($event));

        $paid = $payments->find((int)$attempt->get_id());
        self::assertNotNull($paid);
        self::assertSame(CommercePaymentAttemptStatus::PAID, $paid->get_status());
        self::assertSame('pi_m83c_retry_paid', $paid->get_transaction_id());
        self::assertNotNull($paid->get_paid_at());
    }

    public function test_duplicate_success_callback_keeps_same_paid_attempt(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(self::PURCHASE_UUID, 'stripe', 400, 'EUR');
        $payments->record_provider_launch(
            (int)$attempt->get_id(),
            'cs_m83c_duplicate',
            null,
            'https://checkout.stripe.test/m83c-duplicate'
        );

        $event = $this->success_event(
            (int)$attempt->get_id(),
            'cs_m83c_duplicate',
            'pi_m83c_duplicate'
        );
        $synchronizer = new CommercePaymentEventSynchronizer($payments);

        self::assertTrue($synchronizer->synchronize($event));
        $first = $payments->find((int)$attempt->get_id());
        self::assertNotNull($first);
        self::assertSame(CommercePaymentAttemptStatus::PAID, $first->get_status());

        $firstpaidat = $first->get_paid_at();
        $firsttransaction = $first->get_transaction_id();

        self::assertTrue($synchronizer->synchronize($event));
        $replayed = $payments->find((int)$attempt->get_id());

        self::assertNotNull($replayed);
        self::assertSame(CommercePaymentAttemptStatus::PAID, $replayed->get_status());
        self::assertSame($firstpaidat, $replayed->get_paid_at());
        self::assertSame($firsttransaction, $replayed->get_transaction_id());
        self::assertCount(1, $payments->find_for_purchase(self::PURCHASE_UUID));
    }

    public function test_late_failure_and_expiry_cannot_downgrade_paid_attempt(): void {
        global $DB;

        $payments = new CommercePaymentRepository($DB);
        $attempt = $payments->create(self::PURCHASE_UUID, 'stripe', 400, 'EUR');
        $payments->record_provider_launch(
            (int)$attempt->get_id(),
            'cs_m83c_late_failure',
            null,
            'https://checkout.stripe.test/m83c-late'
        );

        $synchronizer = new CommercePaymentEventSynchronizer($payments);
        self::assertTrue($synchronizer->synchronize($this->success_event(
            (int)$attempt->get_id(),
            'cs_m83c_late_failure',
            'pi_m83c_late_failure'
        )));

        foreach (['payment_failed', 'checkout_expired'] as $eventtype) {
            $event = new InternalEvent($eventtype, [
                'meta' => [
                    'provider' => 'stripe',
                    'commerce_payment_id' => (string)$attempt->get_id(),
                    'commerce_purchase_uuid' => self::PURCHASE_UUID,
                    'session' => 'cs_m83c_late_failure',
                    'payment_intent' => 'pi_m83c_late_failure',
                ],
            ]);

            self::assertTrue($synchronizer->synchronize($event));
            self::assertSame(
                CommercePaymentAttemptStatus::PAID,
                $payments->find((int)$attempt->get_id())?->get_status(),
                'A late provider failure/expiry must never downgrade durable payment success.'
            );
        }
    }

    public function test_paid_purchase_completer_has_serialized_replay_short_circuit(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/fulfillment/native/checkout/' .
            'CommerceNativePaidPurchaseCompleter.php'
        );

        self::assertIsString($source);

        $lockfactory = strpos($source, "local_subscriptions_commerce_paid_purchase");
        $reload = strpos($source, "Another callback may have completed the purchase");
        $fulfilledcheck = strpos(
            $source,
            "if ((string)\$purchase->status === CommercePurchaseStatus::FULFILLED)"
        );
        $planner = strpos($source, 'CommerceNativePurchaseGrantPlanner');

        self::assertNotFalse($lockfactory);
        self::assertNotFalse($reload);
        self::assertNotFalse($fulfilledcheck);
        self::assertNotFalse($planner);
        self::assertLessThan($reload, $lockfactory);
        self::assertLessThan($fulfilledcheck, $reload);
        self::assertLessThan(
            $planner,
            $fulfilledcheck,
            'A fulfilled replay must return before grant planning/fulfillment can run again.'
        );
    }

    private function success_event(int $paymentid, string $session, string $paymentintent): InternalEvent {
        return new InternalEvent('checkout_completed', [
            'amount_minor' => 400,
            'currency' => 'EUR',
            'meta' => [
                'provider' => 'stripe',
                'commerce_payment_id' => (string)$paymentid,
                'commerce_purchase_uuid' => self::PURCHASE_UUID,
                'session' => $session,
                'payment_intent' => $paymentintent,
                'payment_status' => 'paid',
                'checkout_status' => 'complete',
            ],
        ]);
    }

    private function insert_purchase(): void {
        global $DB;

        $now = time();
        $DB->insert_record(
            CommercePersistenceSchema::TABLE_PURCHASE,
            (object)[
                'purchaseuuid' => self::PURCHASE_UUID,
                'reference' => self::PURCHASE_REFERENCE,
                'type' => 'bundle',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'm83c@example.test',
                'status' => CommercePurchaseStatus::PAYMENT_PENDING,
                'currency' => 'EUR',
                'subtotalminor' => 400,
                'discountminor' => 0,
                'totalminor' => 400,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }
}
