<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h3_wallet_admin_policy_test extends \advanced_testcase {

    public function test_express_wallet_candidates_come_from_current_commerce_availability(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString('$availabilityresolver->available(', $checkout);
        self::assertStringContainsString('$expressroutes = array_values(', $checkout);
        self::assertStringContainsString('$expresspaymentmethods = array_values(', $checkout);
        self::assertStringContainsString('$expresswalletmethods =', $checkout);
    }

}
