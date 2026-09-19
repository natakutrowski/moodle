<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f36_paypal_moodle_curl_bootstrap_test extends advanced_testcase {
    public function test_paypal_gateway_bootstraps_moodle_curl_client(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalRestPaymentGateway.php'
        );

        $this->assertStringContainsString(
            "require_once(\$CFG->libdir . '/filelib.php');",
            $contents
        );
        $this->assertStringContainsString(
            'private function new_curl(): \\curl',
            $contents
        );

        // OAuth and Orders calls both go through the bootstrapped helper.
        $this->assertSame(
            2,
            substr_count(
                $contents,
                '$curl = $this->new_curl();'
            )
        );

        $this->assertStringNotContainsString(
            '$curl = new \\curl();',
            $contents
        );
    }
}
