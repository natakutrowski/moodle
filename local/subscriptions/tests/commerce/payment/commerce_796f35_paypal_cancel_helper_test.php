<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796f35_paypal_cancel_helper_test extends advanced_testcase {
    public function test_paypal_cancel_helper_is_defined_and_used(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/paypal/'
            . 'PayPalCommercePaymentProvider.php'
        );

        $this->assertStringContainsString(
            '$cancelurl = $this->build_cancel_return_url(',
            $contents
        );
        $this->assertStringContainsString(
            'private function build_cancel_return_url(',
            $contents
        );
        $this->assertStringContainsString(
            "\$url->param('result', 'cancel');",
            $contents
        );
        $this->assertStringContainsString(
            "\$url->param('provider', self::KEY);",
            $contents
        );
        $this->assertStringContainsString(
            'return $url->out(false);',
            $contents
        );
    }
}
