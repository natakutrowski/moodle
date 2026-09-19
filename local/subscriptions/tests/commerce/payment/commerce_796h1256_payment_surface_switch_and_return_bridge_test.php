<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1256_payment_surface_switch_and_return_bridge_test extends \advanced_testcase {
    public function test_cross_origin_hosted_card_dom_autoclick_is_removed(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringNotContainsString(
            'trySelectHostedCard',
            $amd
        );
        self::assertStringNotContainsString(
            'iframe.contentDocument',
            $amd
        );
        self::assertStringNotContainsString(
            'data-test-id="payByCard"',
            $amd
        );
    }

    public function test_switching_away_hides_alfa_iframe_and_switching_back_reuses_it(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'const hideAlfaCardSurface = () => {',
            $amd
        );
        self::assertStringContainsString(
            'const showPreparedCardSurface = () => {',
            $amd
        );
        self::assertStringContainsString(
            'const syncPaymentSurface = () => {',
            $amd
        );
        self::assertStringContainsString(
            "method !== 'card'",
            $amd
        );
        self::assertStringContainsString(
            'iframeMounted = true;',
            $amd
        );
        self::assertStringContainsString(
            '[data-quick-payment-action]',
            $amd
        );
    }

    public function test_iframe_return_uses_lightweight_bridge_before_moodle_reconciliation(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaCommercePaymentProvider.php'
        );
        $bridge = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/payment/embedded_return_bridge.php'
        );

        self::assertStringContainsString(
            '/local/subscriptions/payment/embedded_return_bridge.php',
            $provider
        );
        self::assertStringContainsString(
            "'target' =>",
            $provider
        );
        self::assertStringContainsString(
            'intentionally does not bootstrap moodle',
            strtolower($bridge)
        );
        self::assertStringContainsString(
            'campus-payment-finalizing-overlay',
            $bridge
        );
        self::assertStringContainsString(
            'window.location.replace(target);',
            $bridge
        );
        self::assertStringNotContainsString(
            'require_once',
            $bridge
        );
    }
}
