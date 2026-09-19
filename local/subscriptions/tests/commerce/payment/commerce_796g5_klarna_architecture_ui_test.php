<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g5_klarna_architecture_ui_test extends advanced_testcase {
    public function test_architecture_marks_market_dependent_methods(): void {
        global $CFG;

        $inspector = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureInspector.php'
        );
        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );

        $this->assertStringContainsString(
            "'marketdependent'",
            $inspector
        );
        $this->assertStringContainsString(
            'commerce_payment_architecture_market_note',
            $page
        );
        $this->assertStringContainsString(
            'commerce_payment_architecture_market_dependent',
            $page
        );
    }
}
