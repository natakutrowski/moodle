<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h427_test_stabilization_test extends advanced_testcase {
    public function test_wallet_cleanup_contract_tracks_current_dynamic_wallet_api(): void {
        global $CFG;

        $test = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/tests/commerce/checkout/'
            . 'commerce_796h426_wallet_diagnostics_only_cleanup_test.php'
        );
        self::assertIsString($test);

        self::assertStringContainsString(
            'expressPaymentMethodTypes(',
            $test
        );
        self::assertStringContainsString(
            "'availablepaymentmethodschange'",
            $test
        );
        self::assertStringContainsString(
            'express.mount(mount);',
            $test
        );
    }
}
