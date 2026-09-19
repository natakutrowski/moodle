<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h106_alfa_widget_diagnostics_test extends advanced_testcase {

    public function test_checkout_keeps_headless_developer_alfa_diagnostics(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        foreach ([
            '$alfawidgetdebugenabled',
            "'token_present'",
            "'token_length'",
            "'alfawidgetserverdiagnosticsjson'",
            '$alfawidgetdebugenabled ? $alfawidgetserverdiagnostics : []',
        ] as $expected) {
            $this->assertStringContainsString($expected, $checkout);
        }
        foreach ([
            'data-checkout-alfa-debug',
            'data-checkout-alfa-debug-copy',
            'data-checkout-alfa-debug-output',
        ] as $removed) {
            $this->assertStringNotContainsString($removed, $template);
        }
        $this->assertStringContainsString('data-server-diagnostics="{{alfawidgetserverdiagnosticsjson}}"', $template);
    }


    public function test_js_diagnostics_cover_widget_bootstrap_without_raw_token_logging(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        foreach ([
            "const BUILD_ID = '7.96H10.10'",
            "'Prepare request starting'",
            "'Prepare payload decoded'",
            "'Widget mount created'",
            "'Official widget wake requested'",
            "'Official widget init called'",
            "'Waiting for official button'",
            "'Official button timeout'",
            "'Alfa flow failed'",
            'window.__campusAlfaWidgetDebug',
            "key.toLowerCase().includes('token')",
            'tokenPresent:',
            'tokenLength:',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
        $this->assertStringNotContainsString('console.info(', $js);
        $this->assertStringNotContainsString('console.error(', $js);
    }

}
