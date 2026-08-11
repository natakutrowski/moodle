<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutPaymentLaunchRecorder;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentInitialization;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderValidationResult;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;
use local_subscriptions\commerce\payment\result\CommercePaymentResult;

/**
 * @covers \local_subscriptions\commerce\checkout\unified\CommerceCheckoutPaymentLaunchRecorder
 */
final class commerce_795h44b_provider_launch_recording_test extends advanced_testcase {
    private const PURCHASE_UUID = '1123456789abcdef0123456789abcdef';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->insert_purchase();
    }

    public function test_stripe_launch_is_recorded_on_the_existing_attempt(): void {
        global $DB;

        $repository = new CommercePaymentRepository($DB);
        $attempt = $repository->create(
            self::PURCHASE_UUID,
            'stripe',
            26400,
            'EUR',
            ['source' => 'unified_checkout']
        );
        $result = CommercePaymentResult::requires_action(
            'cmp_h44b_stripe',
            'stripe',
            'cs_test_h44b_123',
            CommercePaymentAction::redirect('https://checkout.stripe.test/c/pay/cs_test_h44b_123'),
            [
                'stripe_session_id' => 'cs_test_h44b_123',
                'stripe_status' => 'open',
            ]
        );
        $initialization = new CommercePaymentInitialization(
            'cmp_h44b_stripe',
            'stripe',
            CommercePaymentProviderValidationResult::valid(),
            $result,
            false,
            12
        );

        $recorded = (new CommerceCheckoutPaymentLaunchRecorder($repository))->record(
            $attempt,
            $initialization
        );

        $this->assertSame('redirected', $recorded->get_status());
        $this->assertSame('cs_test_h44b_123', $recorded->get_provider_reference());
        $this->assertNull($recorded->get_provider_order_id());
        $this->assertSame(
            'https://checkout.stripe.test/c/pay/cs_test_h44b_123',
            $recorded->get_payment_url()
        );
        $this->assertSame('stripe', $recorded->get_provider_payload()['provider']);
        $this->assertSame('cs_test_h44b_123', $recorded->get_provider_payload()['provider_payment_id']);
        $this->assertCount(1, $repository->find_for_purchase(self::PURCHASE_UUID));
    }

    public function test_alfa_launch_records_the_provider_order_id(): void {
        global $DB;

        $repository = new CommercePaymentRepository($DB);
        $attempt = $repository->create(
            self::PURCHASE_UUID,
            'alfa',
            2970000,
            'RUB'
        );
        $result = CommercePaymentResult::requires_action(
            'cmp_h44b_alfa',
            'alfa',
            'alfa-order-h44b-123',
            CommercePaymentAction::redirect('https://alfa.test/payment/form/123'),
            ['alfa_order_id' => 'alfa-order-h44b-123']
        );
        $initialization = new CommercePaymentInitialization(
            'cmp_h44b_alfa',
            'alfa',
            CommercePaymentProviderValidationResult::valid(),
            $result,
            false,
            18
        );

        $recorded = (new CommerceCheckoutPaymentLaunchRecorder($repository))->record(
            $attempt,
            $initialization
        );

        $this->assertSame('alfa-order-h44b-123', $recorded->get_provider_reference());
        $this->assertSame('alfa-order-h44b-123', $recorded->get_provider_order_id());
        $this->assertSame('redirected', $recorded->get_status());
    }

    private function insert_purchase(): void {
        global $DB;

        $DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object) [
                'purchaseuuid' => self::PURCHASE_UUID,
                'reference' => 'PUR-H44B-00000000000000001',
                'type' => 'subscription',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => null,
                'customeremail' => 'h44b@example.test',
                'status' => 'pending',
                'currency' => 'EUR',
                'subtotalminor' => 26400,
                'discountminor' => 0,
                'totalminor' => 26400,
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
