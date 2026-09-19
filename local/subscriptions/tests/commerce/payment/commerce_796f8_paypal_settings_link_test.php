<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f8_paypal_settings_link_test extends advanced_testcase {
    public function test_paypal_operational_page_links_to_current_commerce_payment_configuration(): void {
        global $CFG;

        $providerpage = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/provider.php'
        );
        $paymentsection = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            "['section' => 'payments']",
            $providerpage
        );
        $this->assertStringContainsString(
            "Provider::PAYPAL",
            $providerpage
        );
        $this->assertStringContainsString(
            "'paypal_env'",
            $paymentsection
        );
        $this->assertStringContainsString(
            "'paypal_sandbox_client_id'",
            $paymentsection
        );
        $this->assertStringContainsString(
            "'paypal_live_client_id'",
            $paymentsection
        );
    }
}
