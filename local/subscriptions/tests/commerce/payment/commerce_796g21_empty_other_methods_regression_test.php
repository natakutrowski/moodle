<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g21_empty_other_methods_regression_test extends advanced_testcase {
    public function test_h12_keeps_non_express_methods_visible_without_other_methods_disclosure(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );

        $this->assertStringNotContainsString(
            '{{#hasothermethods}}',
            $template
        );
        $this->assertStringContainsString(
            '$primary = array_values(',
            $presenter
        );
        $this->assertStringContainsString(
            "empty(\$item['isquickaction'])",
            $presenter
        );
        $this->assertStringContainsString(
            '$others = [];',
            $presenter
        );

        // Apple Pay is filtered before policy/presentation, so it cannot create
        // an invisible "other method" that leaves an empty disclosure behind.
        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertStringContainsString(
            '$expresspaymentmethods',
            $checkout
        );
        $this->assertStringContainsString(
            '!in_array(',
            $checkout
        );
    }
}
