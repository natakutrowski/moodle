<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g2_apple_pay_client_eligibility_test extends advanced_testcase {
    public function test_g21_keeps_apple_pay_out_of_current_hosted_checkout(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethod::APPLE_PAY',
            $checkout
        );
        $this->assertStringContainsString(
            '$availability->get_method()',
            $checkout
        );
        $this->assertStringNotContainsString(
            'apple_pay_eligibility',
            $checkout
        );
    }
}
