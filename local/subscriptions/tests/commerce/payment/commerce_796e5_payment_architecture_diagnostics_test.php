<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e5_payment_architecture_diagnostics_test extends advanced_testcase {
    public function test_payment_configuration_exposes_read_only_architecture_diagnostic(): void {
        global $CFG;

        $section = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );
        $diagnostic = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        );

        $this->assertStringContainsString(
            'commerce_payment_architecture_open',
            $section
        );
        $this->assertStringContainsString(
            'CommercePaymentArchitectureInspector',
            $diagnostic
        );
        $this->assertStringContainsString(
            '$inspector->methods($currency)',
            $diagnostic
        );
        $this->assertStringNotContainsString(
            'set_config(',
            $diagnostic
        );
    }

    public function test_diagnostics_cover_all_current_customer_facing_methods(): void {
        global $CFG;

        $diagnostic = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/payment_architecture.php'
        );

        foreach ([
            'card',
            'apple_pay',
            'google_pay',
            'paypal',
        ] as $method) {
            $this->assertStringContainsString(
                "'" . $method . "'",
                $diagnostic
            );
        }
    }
}
