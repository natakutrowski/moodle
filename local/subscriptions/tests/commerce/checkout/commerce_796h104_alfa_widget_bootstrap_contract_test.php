<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h104_alfa_widget_bootstrap_contract_test extends advanced_testcase {
    public function test_official_script_is_present_with_required_id(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            "'alfawidgetscripturl'",
            $checkout
        );
        $this->assertStringContainsString(
            'id="alfa-payment-script"',
            $template
        );
        $this->assertStringContainsString(
            'src="{{alfawidgetscripturl}}"',
            $template
        );
    }

    public function test_campus_cta_is_hidden_only_after_official_button_exists(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        foreach ([
            'waitForOfficialButton',
            "'#alfa-payment__button'",
            'await waitForOfficialButton(',
            'prepared = true;',
            'submit.hidden = true;',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }

        $this->assertStringNotContainsString(
            'const loadScript =',
            $js
        );
    }
}
