<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\payment\attempt\CommercePaymentAttemptStatus;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;

/**
 * @covers \local_subscriptions\commerce\payment\repository\CommercePaymentRepository
 */
final class commerce_payment_repository_test extends advanced_testcase {

    private const PURCHASE_UUID = '0123456789abcdef0123456789abcdef';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->insert_purchase();
    }

    public function test_each_create_call_appends_a_new_attempt(): void {
        global $DB;

        $repository = new CommercePaymentRepository($DB);

        $first = $repository->create(
            self::PURCHASE_UUID,
            'stripe',
            8900,
            'EUR',
            ['entrypoint' => 'checkout']
        );
        $second = $repository->create(
            self::PURCHASE_UUID,
            'alfa',
            8900,
            'EUR'
        );

        $this->assertSame(0, $first->get_sequence());
        $this->assertSame(1, $second->get_sequence());
        $this->assertNotSame($first->get_id(), $second->get_id());
        $this->assertCount(2, $repository->find_for_purchase(self::PURCHASE_UUID));
    }

    public function test_provider_launch_and_paid_status_are_persisted(): void {
        global $DB;

        $repository = new CommercePaymentRepository($DB);
        $attempt = $repository->create(
            self::PURCHASE_UUID,
            'stripe',
            8900,
            'EUR'
        );

        $launched = $repository->record_provider_launch(
            (int) $attempt->get_id(),
            'cs_test_native_123',
            'order_native_123',
            'https://checkout.example.test/session/123',
            ['provider_status' => 'open']
        );

        $this->assertSame(CommercePaymentAttemptStatus::REDIRECTED, $launched->get_status());
        $this->assertSame('cs_test_native_123', $launched->get_provider_reference());
        $this->assertSame('order_native_123', $launched->get_provider_order_id());
        $this->assertSame(
            'cs_test_native_123',
            $repository->find_by_provider_reference('stripe', 'cs_test_native_123')?->get_provider_reference()
        );

        $paid = $repository->update_status(
            (int) $attempt->get_id(),
            CommercePaymentAttemptStatus::PAID,
            'pi_native_123',
            ['provider_status' => 'paid'],
            1770000000
        );

        $this->assertSame(CommercePaymentAttemptStatus::PAID, $paid->get_status());
        $this->assertSame('pi_native_123', $paid->get_transaction_id());
        $this->assertSame(1770000000, $paid->get_paid_at());
        $this->assertSame(['provider_status' => 'paid'], $paid->get_provider_payload());
    }

    private function insert_purchase(): void {
        global $DB;

        $DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object) [
                'purchaseuuid' => self::PURCHASE_UUID,
                'reference' => 'PUR-H43-000000000000000001',
                'type' => 'subscription',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'h43@example.test',
                'status' => 'pending',
                'currency' => 'EUR',
                'subtotalminor' => 8900,
                'discountminor' => 0,
                'totalminor' => 8900,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => time(),
                'timemodified' => time(),
            ]
        );
    }
}
