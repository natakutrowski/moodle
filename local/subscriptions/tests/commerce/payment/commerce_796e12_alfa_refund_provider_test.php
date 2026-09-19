<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e12_alfa_refund_provider_test extends advanced_testcase {
    public function test_alfa_provider_implements_common_refund_contracts(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            'CommerceRefundCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceRefundHistoryCapablePaymentProvider',
            $contents
        );
        $this->assertStringContainsString(
            'public function refund(',
            $contents
        );
        $this->assertStringContainsString(
            'public function list_refunds(',
            $contents
        );
    }

    public function test_alfa_legacy_gateway_uses_refund_and_extended_status_apis(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );

        $this->assertStringContainsString(
            "'/payment/rest/refund.do'",
            $contents
        );
        $this->assertStringContainsString(
            'getOrderStatusExtended.do',
            $contents
        );
        $this->assertStringContainsString(
            "'refunds'",
            $contents
        );
        $this->assertStringContainsString(
            "'referenceNumber'",
            $contents
        );
    }
}
