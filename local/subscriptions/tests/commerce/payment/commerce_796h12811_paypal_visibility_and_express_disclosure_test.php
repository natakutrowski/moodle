<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionMode;
use local_subscriptions\commerce\checkout\execution\CommerceCheckoutExecutionPolicy;
use local_subscriptions\commerce\payment\method\CommercePaymentMethod;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12811_paypal_visibility_and_express_disclosure_test extends \advanced_testcase {
    public function test_paypal_embedded_is_still_executable_and_visible_to_checkout_filter(): void {
        self::assertSame(
            CommerceCheckoutExecutionMode::CAMPUS_EMBEDDED,
            CommerceCheckoutExecutionPolicy::mode_for_method(
                CommercePaymentMethod::PAYPAL
            )
        );
        self::assertTrue(
            CommerceCheckoutExecutionPolicy::is_executable_now(
                CommercePaymentMethod::PAYPAL
            )
        );
    }

    public function test_express_disclosure_is_not_covered_by_campus_consent_overlay(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringNotContainsString(
            'commerce-checkout-express-wallets__consent-gate',
            $amd
        );
        self::assertStringNotContainsString(
            'mount.style.pointerEvents',
            $amd
        );
        self::assertStringNotContainsString(
            '.commerce-checkout-express-wallets.is-consent-locked',
            $css
        );
    }

    public function test_express_confirmation_still_requires_checkout_validity(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );

        self::assertStringContainsString(
            "express.on(\n            'confirm'",
            $amd
        );
        self::assertStringContainsString(
            'form.checkValidity',
            $amd
        );
        self::assertStringContainsString(
            "'[data-checkout-terms]'",
            $amd
        );
        self::assertStringContainsString(
            'event?.paymentFailed?.({',
            $amd
        );
    }
}
