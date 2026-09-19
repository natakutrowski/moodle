<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g63_checkout_payment_method_import_test extends advanced_testcase {
    public function test_checkout_imports_payment_method_before_using_constants(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            'use local_subscriptions\\commerce\\payment\\method\\CommercePaymentMethod;',
            $contents
        );

        $import = strpos(
            $contents,
            'use local_subscriptions\\commerce\\payment\\method\\CommercePaymentMethod;'
        );
        $usage = strpos(
            $contents,
            'CommercePaymentMethod::APPLE_PAY'
        );

        $this->assertNotFalse($import);
        $this->assertNotFalse($usage);
        $this->assertLessThan(
            $usage,
            $import
        );
    }
}
