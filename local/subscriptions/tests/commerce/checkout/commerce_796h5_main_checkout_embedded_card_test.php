<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h5_main_checkout_embedded_card_test extends \advanced_testcase {

    public function test_inline_card_intercepts_card_and_uses_ajax_prepare(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );
        self::assertIsString($js);

        self::assertStringContainsString("selectedMethod(form)", $js);
        self::assertStringContainsString('isInlineMethod(', $js);
        self::assertStringContainsString('config.inlineMethods', $js);
        self::assertMatchesRegularExpression(
            "/body\.set\(\s*'paymentmethod'\s*,\s*selectedMethod\(form\)\s*\)/s",
            $js
        );
        self::assertMatchesRegularExpression(
            "/body\.set\(\s*'ajax'\s*,\s*'1'\s*\)/s",
            $js
        );
        self::assertStringContainsString('clientSecret', $js);
    }

}
