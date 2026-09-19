<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1231_ru_amd_payload_and_fast_payment_polish_test extends \advanced_testcase {
    public function test_inline_card_amd_bootstrap_contains_no_localised_copy(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertMatchesRegularExpression(
            "/js_call_amd\(\s*'local_subscriptions\/checkout_inline_card',\s*'init',\s*\[\[/s",
            $checkout
        );

        preg_match(
            "/js_call_amd\(\s*'local_subscriptions\/checkout_inline_card'.*?\n\);/s",
            $checkout,
            $matches
        );
        $block = $matches[0] ?? '';

        self::assertNotSame('', $block);
        self::assertStringContainsString("'locale' => current_language()", $block);
        self::assertStringContainsString(
            "'inlineMethods' => \$inlinepaymentmethods",
            $block
        );
        self::assertStringNotContainsString("'initialSubmitLabel'", $block);
        self::assertStringNotContainsString("'openLabel'", $block);
        self::assertStringNotContainsString("'confirmLabel'", $block);
        self::assertStringNotContainsString("'prepareError'", $block);
        self::assertStringNotContainsString("'confirmError'", $block);
    }

    public function test_inline_card_localised_copy_is_read_from_dom(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        self::assertStringContainsString(
            'data-open-label="{{cardinlineopenlabel}}"',
            $template
        );
        self::assertStringContainsString(
            'data-confirm-label="{{cardinlineconfirmlabel}}"',
            $template
        );
        self::assertStringContainsString(
            'panel.dataset.openLabel',
            $amd
        );
        self::assertStringContainsString(
            'panel.dataset.confirmLabel',
            $amd
        );
        self::assertStringContainsString(
            'panel.dataset.prepareError',
            $amd
        );
        self::assertStringContainsString(
            'panel.dataset.confirmError',
            $amd
        );
    }

    public function test_fast_payment_content_is_centered_and_uses_campus_hover(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        self::assertMatchesRegularExpression(
            '/\.commerce-checkout-fast-payment\s*\{[^}]*display:\s*flex;[^}]*justify-content:\s*center;/s',
            $css
        );
        self::assertStringContainsString(
            'border-color: var(--primary, var(--bs-primary, #ec4899));',
            $css
        );
        self::assertStringContainsString(
            'width: 36px;',
            $css
        );
        self::assertStringContainsString(
            'height: 36px;',
            $css
        );
    }
}
