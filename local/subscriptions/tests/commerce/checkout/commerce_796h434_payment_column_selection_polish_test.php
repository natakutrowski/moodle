<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h434_payment_column_selection_polish_test extends advanced_testcase {
    public function test_provider_choices_are_stacked_in_payment_rail(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            ".commerce-checkout-payment-methods > .d-grid {\n    grid-template-columns: 1fr;",
            $css
        );
    }

    public function test_stale_selected_class_is_visually_neutralized(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        $this->assertStringContainsString(
            '.commerce-checkout-provider.is-selected:not(:has(input:checked))',
            $css
        );
        $this->assertStringContainsString(
            '.commerce-checkout-provider:has(input:checked)',
            $css
        );
    }
}
