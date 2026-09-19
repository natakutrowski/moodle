<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g1_provider_resolution_regression_test extends advanced_testcase {
    public function test_candidates_does_not_recursively_call_itself(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentProviderRegistry.php'
        );

        $this->assertStringNotContainsString(
            '$candidates = $this->candidates($request);',
            $contents
        );
        $this->assertStringContainsString(
            '$provider->supports($request)',
            $contents
        );
    }
}
