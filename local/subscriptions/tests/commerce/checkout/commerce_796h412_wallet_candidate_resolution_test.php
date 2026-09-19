<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h412_wallet_candidate_resolution_test extends advanced_testcase {
    public function test_main_checkout_derives_express_candidates_from_executable_routes(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        foreach ([
            '$expressroutes = array_values(',
            '$expresspaymentmethods = array_values(',
            '$expresswalletmethods =',
            '$expresspaymentmethods;',
            '$hasexpresswalletcandidate =',
        ] as $expected) {
            $this->assertStringContainsString($expected, $checkout);
        }
    }
}
