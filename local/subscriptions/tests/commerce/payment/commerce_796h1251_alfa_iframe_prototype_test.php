<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1251_alfa_iframe_prototype_test extends \advanced_testcase {
    public function test_iframe_mode_is_opt_in_and_widget_setting_is_retained(): void {
        global $CFG;

        $config = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaIframeConfiguration.php'
        );
        $section = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        self::assertStringContainsString(
            "'alfa_iframe_enabled'",
            $config
        );
        self::assertStringContainsString(
            "'alfa_iframe_enabled'",
            $section
        );
        self::assertStringContainsString(
            "'alfa_widget_enabled'",
            $section
        );
    }

    public function test_card_iframe_uses_register_form_url(): void {
        global $CFG;

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/alfa/'
            . 'AlfaCommercePaymentProvider.php'
        );

        self::assertStringContainsString(
            'AlfaIframeConfiguration::is_enabled()',
            $provider
        );
        self::assertStringContainsString(
            '$this->gateway->register(',
            $provider
        );
        self::assertStringContainsString(
            "'embedded_type' => 'alfa_iframe'",
            $provider
        );
        self::assertStringContainsString(
            "'form_url' => \$formurl",
            $provider
        );
    }

    public function test_checkout_mounts_form_url_without_touching_iframe_dom(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        self::assertStringContainsString(
            'data-checkout-alfa-iframe-frame',
            $template
        );
        self::assertStringContainsString(
            'data-checkout-alfa-iframe-fallback',
            $template
        );
        self::assertStringContainsString(
            "preparedPayload.type === 'alfa_iframe'",
            $amd
        );
        self::assertStringContainsString(
            'iframeFrame.src = formUrl;',
            $amd
        );
        self::assertStringNotContainsString(
            'contentDocument',
            $amd
        );
        self::assertStringNotContainsString(
            'contentWindow.document',
            $amd
        );
    }
}
