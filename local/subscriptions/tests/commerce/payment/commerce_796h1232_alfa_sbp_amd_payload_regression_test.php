<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1232_alfa_sbp_amd_payload_regression_test extends \advanced_testcase {
    public function test_alfa_widget_and_sbp_amd_calls_are_argument_free(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertMatchesRegularExpression(
            "/js_call_amd\\(\\s*'local_subscriptions\\/checkout_alfa_widget',\\s*'init'\\s*\\)/s",
            $checkout
        );
        self::assertMatchesRegularExpression(
            "/js_call_amd\\(\\s*'local_subscriptions\\/checkout_alfa_sbp',\\s*'init'\\s*\\)/s",
            $checkout
        );
    }

    public function test_alfa_widget_debug_diagnostics_are_preserved_in_dom(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'data-server-diagnostics="{{alfawidgetserverdiagnosticsjson}}"',
            $template
        );
        self::assertStringContainsString(
            'panel.dataset.serverDiagnostics',
            $amd
        );
        self::assertStringContainsString(
            "panel.dataset.debug === '1'",
            $amd
        );
    }

    public function test_sbp_runtime_configuration_is_read_from_dom(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );

        self::assertStringContainsString(
            'data-prepare-error="{{sbpprepareerror}}"',
            $template
        );
        self::assertStringContainsString(
            'panel.dataset.prepareError',
            $amd
        );
        self::assertStringContainsString(
            'panel.dataset.checkoutLanguage',
            $amd
        );
    }
}
