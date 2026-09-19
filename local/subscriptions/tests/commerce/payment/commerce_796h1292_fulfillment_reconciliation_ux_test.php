<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1292_fulfillment_reconciliation_ux_test extends \advanced_testcase {

    public function test_processing_paid_order_enables_fulfillment_polling(): void {
        global $CFG;

        $result = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        self::assertIsString($result);

        self::assertStringContainsString('$state->code === \'processing\' && $order->is_paid()', $result);
        self::assertStringContainsString("'local_subscriptions/order_fulfillment_poll'", $result);
        self::assertStringContainsString('data-order-fulfillment-watch', $result);
    }


    public function test_fulfillment_endpoint_reuses_guest_session_ownership_and_state_resolver(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/payment/fulfillment_status.php'
        );
        self::assertIsString($endpoint);

        self::assertStringContainsString('require_sesskey();', $endpoint);
        self::assertStringContainsString('CommerceGuestCheckoutSessionRepository', $endpoint);
        self::assertStringContainsString('find_by_token(', $endpoint);
        self::assertStringContainsString('find_for_guest_session(', $endpoint);
        self::assertStringContainsString('CommercePostPaymentStateResolver', $endpoint);
        self::assertStringContainsString("'success' => 'ready'", $endpoint);
    }


    public function test_browser_polls_then_reloads_once_access_is_ready(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/order_fulfillment_poll.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString("result.status === 'ready'", $amd);
        self::assertStringContainsString('window.location.reload();', $amd);
        self::assertStringContainsString('90000', $amd);
        self::assertStringContainsString('2000', $amd);
        self::assertStringContainsString('5000', $amd);
    }


    public function test_manual_refresh_fallback_remains_available(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/order_fulfillment_poll.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString("'[data-order-fulfillment-refresh]'", $amd);
        self::assertStringContainsString("'click'", $amd);
    }

}
