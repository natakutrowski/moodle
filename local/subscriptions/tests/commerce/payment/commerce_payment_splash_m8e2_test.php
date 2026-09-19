<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_payment_splash_m8e2_test extends advanced_testcase {
    public function test_checkout_loads_current_payment_transition_assets(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertStringContainsString(
            'styles/payment_provider_transition.css',
            $source
        );
        self::assertStringContainsString(
            "'local_subscriptions/checkout_submission_state'",
            $source
        );
    }

    public function test_checkout_template_exposes_shared_payment_splash_surface(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            'payment-provider-transition',
            $source
        );
        self::assertStringContainsString(
            'data-checkout-payment-splash',
            $source
        );
        self::assertStringContainsString(
            'data-checkout-wallet-splash',
            $source
        );
    }

    public function test_preview_page_is_admin_only_and_supports_both_modes(): void {
        global $CFG;

        $source = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/payment-splash-preview.php'
        );

        self::assertStringContainsString(
            "require_capability('moodle/site:config'",
            $source
        );
        self::assertStringContainsString(
            "['return', 'outbound']",
            $source
        );
        self::assertStringContainsString(
            'payment-provider-transition',
            $source
        );
        self::assertStringContainsString(
            'alfa-payment-confirmation',
            $source
        );
    }
}
