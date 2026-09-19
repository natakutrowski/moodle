<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g6_payment_presentation_admin_ui_test extends advanced_testcase {
    public function test_payments_configuration_has_provider_and_method_allowlists(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        foreach ([
            'commerce_presented_payment_providers',
            'commerce_presented_payment_methods',
            'multicheck_csv_allowempty',
            'CommercePaymentMethodCatalogue::keys()',
            'provider_paypal',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_allowlists_do_not_require_plugin_schema_upgrade(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            'set_config($key, $clean, \'local_subscriptions\')',
            $contents
        );
    }
}
