<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\payment;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_795h44d_native_return_pipeline_test extends advanced_testcase {
    public function test_return_controller_requires_native_payment_identity(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/return.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("required_param('paymentid'", $source);
        self::assertStringNotContainsString("required_param('pid'", $source);
        self::assertStringNotContainsString("subscription_payment_request", $source);
        self::assertStringContainsString('CommercePaymentReturnResolver', $source);
        self::assertStringContainsString('CommercePaymentRepository', $source);
    }

    public function test_event_router_synchronizes_native_attempt_first(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/payment/EventRouter.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('CommercePaymentEventSynchronizer', $source);
        self::assertStringContainsString('->synchronize($event)', $source);
    }
}
