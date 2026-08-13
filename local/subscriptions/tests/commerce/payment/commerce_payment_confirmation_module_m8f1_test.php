<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_payment_confirmation_module_m8f1_test extends advanced_testcase {
    public function test_order_result_uses_generic_payment_confirmation_module(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            'local_subscriptions/payment_confirmation',
            $source
        );
        self::assertStringNotContainsString(
            'local_subscriptions/alfa_payment_confirmation',
            $source
        );
    }

    public function test_generic_amd_source_and_build_exist(): void {
        global $CFG;

        self::assertFileExists(
            $CFG->dirroot . '/local/subscriptions/amd/src/payment_confirmation.js'
        );
        self::assertFileExists(
            $CFG->dirroot . '/local/subscriptions/amd/build/payment_confirmation.min.js'
        );
    }
}
