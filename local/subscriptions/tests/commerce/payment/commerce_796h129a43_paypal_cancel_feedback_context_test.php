<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a43_paypal_cancel_feedback_context_test extends \advanced_testcase {
    public function test_cancel_feedback_is_positioned_next_to_paypal_action(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "'[data-payment-method=\"paypal\"]'",
            $amd
        );
        self::assertStringContainsString(
            "'[data-payment-method-card=\"paypal\"]'",
            $amd
        );
        self::assertStringContainsString(
            "paypalCard.insertAdjacentElement(\n            'afterend'",
            $amd
        );
    }

    public function test_any_new_payment_action_clears_paypal_cancel_feedback(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            'const clearCancelFeedback = () => {',
            $amd
        );
        self::assertStringContainsString(
            "'[data-payment-method], [data-payment-method-card], [data-payment-action-card]'",
            $amd
        );
        self::assertStringContainsString(
            "target.name === 'paymentmethod'",
            $amd
        );
        self::assertStringContainsString(
            'clearCancelFeedback();',
            $amd
        );
    }

    public function test_feedback_is_compact_near_action_card(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            '.commerce-checkout-paypal-cancel-feedback {',
            $css
        );
        self::assertStringContainsString(
            'margin: .4rem 0 .55rem;',
            $css
        );
        self::assertStringContainsString(
            'font-size: .84rem;',
            $css
        );
    }
}
