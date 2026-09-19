<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g81_provider_configuration_completion_test extends advanced_testcase {
    public function test_payments_hub_contains_paypal_environment_and_all_provider_credentials(): void {
        global $CFG;
        $contents = file_get_contents($CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/section.php');
        foreach (['paypal_env', 'stripe_test_secret', 'alfa_test_username', 'alfa_test_refund_username', 'paypal_sandbox_client_secret', 'paypal_live_webhook_id', 'password_keep'] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_all_three_providers_share_connection_test_service(): void {
        global $CFG;
        $contents = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/payment/provider/CommercePaymentProviderConnectionTestService.php');
        foreach (['Provider::STRIPE', 'Provider::ALFA', 'Provider::PAYPAL', 'Balance::retrieve', 'getOrderStatusExtended.do', 'test_connection()'] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

    public function test_obsolete_paypal_page_is_removed(): void {
        global $CFG;
        $this->assertFileDoesNotExist($CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/paypal.php');
    }
}
