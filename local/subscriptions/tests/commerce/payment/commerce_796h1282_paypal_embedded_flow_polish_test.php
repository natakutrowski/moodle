<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1282_paypal_embedded_flow_polish_test extends \advanced_testcase {
    public function test_paypal_start_is_guarded_against_double_execution(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString('let starting = false;', $amd);
        self::assertStringContainsString(
            "if (starting) {\n                return;",
            $amd
        );
        self::assertStringContainsString('starting = true;', $amd);
        self::assertStringContainsString('let startGeneration = 0;', $amd);
    }

    public function test_paypal_preparation_splash_stops_when_modal_takes_over(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "showPaymentSplash(\n                'preparing'",
            $amd
        );
        self::assertStringContainsString(
            '// PayPal now owns the visible payment surface.',
            $amd
        );
        self::assertStringContainsString('hidePaymentSplash();', $amd);
    }

    public function test_cancel_and_error_clear_stale_paypal_state(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "onCancel:\n                    () => {\n                        startGeneration += 1;",
            $amd
        );
        self::assertStringContainsString(
            "onError:\n                    () => {\n                        startGeneration += 1;",
            $amd
        );
        self::assertGreaterThanOrEqual(
            3,
            substr_count($amd, 'activeOrder = null;')
        );
    }

    public function test_hosted_fallback_is_preserved(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString('payload?.fallbackUrl', $amd);
        self::assertStringContainsString(
            "showPaymentSplash(\n                                'redirect'",
            $amd
        );
        self::assertStringContainsString('window.location.assign(', $amd);
    }

    public function test_validated_splash_from_h12812_is_preserved(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "showPaymentSplash(\n                            'validated'",
            $amd
        );
    }
}
