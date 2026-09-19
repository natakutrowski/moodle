<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1252_alfa_iframe_polish_and_test_env_test extends \advanced_testcase {
    public function test_iframe_disables_scrollbars_and_external_fallback_is_centered(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/checkout_express_wallets.css'
        );

        self::assertStringContainsString('scrolling="no"', $template);
        self::assertStringContainsString(
            'commerce-checkout-alfa-iframe__external-icon',
            $template
        );
        self::assertStringContainsString(
            '.commerce-checkout-alfa-iframe [data-checkout-alfa-iframe-fallback]',
            $css
        );
        self::assertStringContainsString(
            'display: inline-flex;',
            $css
        );
        self::assertStringContainsString(
            'align-items: center;',
            $css
        );
        self::assertStringContainsString(
            'justify-content: center;',
            $css
        );
        self::assertStringContainsString('overflow: hidden;', $css);
    }

    public function test_iframe_keeps_normal_register_api_path_without_widget_token_dependency(): void {
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
            'no OpenID widget token',
            $provider
        );
        self::assertStringNotContainsString(
            'prepare_widget(' . PHP_EOL
            . '                        $gatewayrequest' . PHP_EOL
            . '                    );' . PHP_EOL
            . '            } else if (' . PHP_EOL
            . '                $executionmode',
            ''
        );
    }
}
