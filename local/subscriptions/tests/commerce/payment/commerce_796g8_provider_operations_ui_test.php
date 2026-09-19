<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g8_provider_operations_ui_test extends advanced_testcase {
    public function test_payments_section_is_provider_hub(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentProviderOperationalStatusService',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentProviderOperationalRenderer::overview_card',
            $contents
        );
        $this->assertStringContainsString(
            "['stripe', 'alfa', 'paypal']",
            $contents
        );
    }

    public function test_common_provider_page_handles_all_three_providers(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/provider.php'
        );

        foreach ([
            'Provider::STRIPE',
            'Provider::ALFA',
            'Provider::PAYPAL',
            'CommercePaymentProviderOperationalRenderer::status_table',
            "['section' => 'payments']",
        ] as $expected) {
            $this->assertStringContainsString($expected, $contents);
        }
    }

}
