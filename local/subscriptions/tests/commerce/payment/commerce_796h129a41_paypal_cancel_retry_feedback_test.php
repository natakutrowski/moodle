<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a41_paypal_cancel_retry_feedback_test extends \advanced_testcase {
    public function test_paypal_cancel_is_neutral_feedback_and_resets_session_state(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "const setFeedback = (",
            $amd
        );
        self::assertStringContainsString(
            "'alert-info'",
            $amd
        );
        self::assertStringContainsString(
            "onCancel:",
            $amd
        );
        self::assertStringContainsString(
            "config.cancelledMessage,\n                            'info'",
            $amd
        );
        self::assertStringContainsString(
            'starting = false;',
            $amd
        );
        self::assertStringContainsString(
            'activeOrder = null;',
            $amd
        );
    }

    public function test_new_paypal_attempt_clears_previous_feedback(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );

        self::assertStringContainsString(
            "setError(\n                errorNode,\n                ''",
            $amd
        );
        self::assertStringContainsString(
            "presentationMode:\n                        'auto'",
            $amd
        );
    }

    public function test_cancel_message_exists_in_all_checkout_languages(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/lang/'
                . $lang
                . '/local_subscriptions.php'
            );

            self::assertStringContainsString(
                'commerce_checkout_paypal_cancelled',
                $source
            );
        }
    }

    public function test_cancel_feedback_has_non_error_visual_treatment(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            '[data-checkout-paypal-error].alert-info:not([hidden])',
            $css
        );
    }
}
