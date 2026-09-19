<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a42_paypal_visible_cancel_feedback_test extends \advanced_testcase {

    public function test_cancel_feedback_is_created_outside_hidden_paypal_configuration_node(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('const cancellationFeedbackNode = (', $amd);
        self::assertStringContainsString("'[data-checkout-paypal-cancel-feedback]'", $amd);
        self::assertStringContainsString("document.createElement(\n            'div'", $amd);
        self::assertStringContainsString('insertAdjacentElement(', $amd);
        self::assertStringContainsString("'afterend'", $amd);
    }


    public function test_on_cancel_uses_visible_feedback_and_retry_clears_it(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        self::assertIsString($amd);

        $cancel = strpos($amd, 'onCancel:');
        $cancelmessage = strpos($amd, 'config.cancelledMessage', $cancel);
        $clear = strpos($amd, 'const clearCancelFeedback = () =>');
        $presentation = strpos($amd, "presentationMode:");
        self::assertNotFalse($cancel);
        self::assertNotFalse($cancelmessage);
        self::assertNotFalse($clear);
        self::assertNotFalse($presentation);
        self::assertGreaterThan($cancel, $cancelmessage);
        self::assertStringContainsString("'auto'", substr($amd, $presentation, 120));
        self::assertStringContainsString('clearCancelFeedback();', $amd);
    }


    public function test_cancel_feedback_has_compact_neutral_style(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );
        self::assertIsString($css);

        self::assertStringContainsString('.commerce-checkout-paypal-cancel-feedback {', $css);
        self::assertStringContainsString('margin: .65rem 0 0;', $css);
    }

}
