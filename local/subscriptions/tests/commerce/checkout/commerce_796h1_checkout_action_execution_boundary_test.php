<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1_checkout_action_execution_boundary_test extends advanced_testcase {
    public function test_action_is_market_aware_and_rejects_unimplemented_execution_modes(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout_action.php'
        );

        $this->assertStringContainsString(
            '$paymentcountry = Region::detect_country();',
            $contents
        );
        $this->assertStringContainsString(
            '$paymentcountry',
            $contents
        );
        $this->assertStringContainsString(
            'CommerceCheckoutExecutionPolicy::is_executable_now(',
            $contents
        );
        $this->assertStringContainsString(
            "'payment_execution_mode'",
            $contents
        );
    }
}
