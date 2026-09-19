<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1253_iframe_return_and_legal_card_test extends \advanced_testcase {
    public function test_alfa_iframe_marks_return_as_embedded_for_top_navigation(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaCommercePaymentProvider.php'
        );
        $return = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/payment/return.php'
        );

        self::assertStringContainsString(
            "['embedded' => 1]",
            $provider
        );
        self::assertStringContainsString(
            'if ($embedded) {',
            $return
        );
        self::assertStringContainsString(
            'window.top.location.href=',
            $return
        );
    }

    public function test_iframe_uses_generous_outer_scroll_height(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        self::assertStringContainsString(
            'height: 860px;',
            $css
        );
        self::assertStringContainsString(
            'height: 940px;',
            $css
        );
        self::assertStringContainsString(
            'overflow: hidden;',
            $css
        );
    }

    public function test_legal_card_is_clickable_without_hijacking_links(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/guest_checkout_security.js'
        );

        self::assertStringContainsString(
            'data-checkout-legal-card',
            $template
        );
        self::assertStringContainsString(
            "'a, input, label, button, select, textarea'",
            $amd
        );
        self::assertStringContainsString(
            "card.addEventListener('click'",
            $amd
        );
        self::assertStringContainsString(
            "card.addEventListener('keydown'",
            $amd
        );
        self::assertStringContainsString(
            "new Event('change'",
            $amd
        );
    }
}
