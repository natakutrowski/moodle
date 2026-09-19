<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression coverage for the live Alfa Payment Widget contract validated in H10.8.
 */
final class commerce_796h108_alfa_live_gateway_ux_test extends advanced_testcase {
    public function test_widget_language_falls_back_to_english_outside_russian(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'LegacyAlfaPaymentGateway.php'
        );

        $this->assertStringContainsString(
            "str_starts_with(\$language, 'ru')",
            $source
        );
        $this->assertStringContainsString(
            "? 'ru'",
            $source
        );
        $this->assertStringContainsString(
            ": 'en';",
            $source
        );
    }

    public function test_alfa_diagnostics_are_headless_and_require_debug_developer(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "\$alfawidgetdebugenabled =\n    debugging('', DEBUG_DEVELOPER);",
            $source
        );
        $this->assertStringNotContainsString(
            "debugging('', DEBUG_DEVELOPER)\n    ||",
            $source
        );

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertStringNotContainsString(
            'data-checkout-alfa-debug',
            $template
        );
    }

    public function test_official_alfa_button_is_visually_integrated_with_checkout(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/guest_checkout.css'
        );

        foreach ([
            '.commerce-checkout-alfa-widget #alfa-payment__button',
            'background: var(--bs-primary) !important;',
            'border-radius: .8rem !important;',
            'min-height: 3.25rem !important;',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $css
            );
        }
    }
}
