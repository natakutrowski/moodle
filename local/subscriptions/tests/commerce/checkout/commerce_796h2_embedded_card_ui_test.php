<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h2_embedded_card_ui_test extends \advanced_testcase {

    public function test_inline_card_uses_neutral_processing_splash_and_ajax_prepare(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );
        self::assertIsString($js);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertMatchesRegularExpression(
            "/body\.set\(\s*'ajax'\s*,\s*'1'\s*\)/s",
            $js
        );
        self::assertStringContainsString('clientSecret', $js);
        self::assertStringContainsString('showPaymentSplash(', $js);
        self::assertStringContainsString('data-checkout-payment-splash', $template);
        self::assertStringContainsString('data-processing-title', $template);
    }

}
