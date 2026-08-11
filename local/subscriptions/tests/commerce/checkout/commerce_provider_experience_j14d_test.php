<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract tests for the J14D provider transition experience.
 *
 * @coversNothing
 */
final class commerce_provider_experience_j14d_test extends \advanced_testcase {
    public function test_checkout_template_uses_provider_experience(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/checkout/page.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('data-provider-experience', $template);
        self::assertStringContainsString('local_subscriptions/provider_experience', $template);
        self::assertStringContainsString('checkout/provider_experience', $template);
    }

    public function test_express_entrypoints_are_intercepted(): void {
        $showroom = file_get_contents(__DIR__ . '/../../../templates/showroom/offer.mustache');
        $card = file_get_contents(__DIR__ . '/../../../templates/storefront/product_card.mustache');
        $panel = file_get_contents(__DIR__ . '/../../../templates/storefront/product_commerce_panel.mustache');

        self::assertStringContainsString('data-provider-experience', (string)$showroom);
        self::assertStringContainsString('data-provider-experience', (string)$card);
        self::assertStringContainsString('data-provider-experience', (string)$panel);
    }

    public function test_alfa_vpn_warning_is_translated(): void {
        foreach (['fr', 'en', 'ru'] as $lang) {
            $strings = file_get_contents(__DIR__ . '/../../../lang/' . $lang . '/local_subscriptions.php');
            self::assertStringContainsString(
                "\$string['commerce_provider_experience_alfa_advice']",
                (string)$strings
            );
        }
    }
}
