<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
final class commerce_796h1254_zero_click_iframe_and_splash_test extends \advanced_testcase {
    public function test_card_selection_keeps_explicit_cta_and_busy_guard(): void {
        global $CFG;
        $amd = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js');
        self::assertIsString($amd);

        self::assertStringContainsString(
            'selectedMethod(form)',
            $amd
        );
        self::assertStringContainsString(
            "!== 'card'",
            $amd
        );
        self::assertStringContainsString(
            'submit.disabled = preparing;',
            $amd
        );
        self::assertStringContainsString(
            'if (prepared) {',
            $amd
        );
        self::assertStringContainsString(
            'submit.hidden = false;',
            $amd
        );
        self::assertStringNotContainsString(
            'form.requestSubmit(submit);',
            $amd
        );
    }
    public function test_register_delay_has_immediate_local_loading_feedback(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $amd = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js');
        self::assertStringContainsString('data-checkout-alfa-iframe-loading', $template);
        self::assertStringContainsString('showIframeLoading();', $amd);
        self::assertStringContainsString('hideIframeLoading();', $amd);
    }
    public function test_embedded_return_renders_fullscreen_splash_before_top_navigation(): void {
        global $CFG;
        $return = file_get_contents($CFG->dirroot . '/local/subscriptions/payment/return.php');
        self::assertStringContainsString('commerce_payment_splash_finalizing_title', $return);
        self::assertStringContainsString('commerce_payment_splash_finalizing_message', $return);
        self::assertStringContainsString('window.top.document', $return);
        self::assertStringContainsString('window.top.location.href=url', $return);
    }
}
