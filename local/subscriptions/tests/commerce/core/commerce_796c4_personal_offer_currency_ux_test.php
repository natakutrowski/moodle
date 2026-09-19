<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\crm\commerce\rendering\CommercePersonalOfferConditionsRenderer;

/** Tests 7.96C4 personal-offer currency UX generalization. */
final class commerce_796c4_personal_offer_currency_ux_test extends advanced_testcase {
    public function test_shared_pricing_renderer_uses_central_labels_and_minor_unit_steps(): void {
        $html = CommercePersonalOfferConditionsRenderer::pricing(['EUR', 'JPY', 'RUB', 'TND']);

        self::assertStringContainsString('🇪🇺 EUR (€)', $html);
        self::assertStringContainsString('🇯🇵 JPY (¥)', $html);
        self::assertStringContainsString('🇷🇺 RUB (₽)', $html);
        self::assertStringContainsString('🇹🇳 TND (د.ت)', $html);

        self::assertMatchesRegularExpression('/name="amount_jpy"[^>]*step="1"|step="1"[^>]*name="amount_jpy"/', $html);
        self::assertMatchesRegularExpression('/name="amount_tnd"[^>]*step="0\.001"|step="0\.001"[^>]*name="amount_tnd"/', $html);
    }

    public function test_personal_offer_admin_surfaces_do_not_keep_currency_specific_symbol_maps(): void {
        $root = dirname(__DIR__, 3);
        $files = [
            '/classes/crm/commerce/rendering/CommercePersonalOfferConditionsRenderer.php',
            '/admin/commerce/personal-offers/edit.php',
            '/admin/commerce/personal-offers/view.php',
        ];

        foreach ($files as $relative) {
            $source = file_get_contents($root . $relative);
            self::assertIsString($source, $relative);
            self::assertStringNotContainsString("'EUR' => '€', 'RUB' => '₽'", $source, $relative);
            self::assertStringNotContainsString("\$currency==='EUR'?' (€)':' (₽)'", $source, $relative);
        }

        $renderer = file_get_contents($root . $files[0]);
        self::assertStringContainsString('CommerceCurrencyLabelFormatter::format($code)', $renderer);
        self::assertStringContainsString('Currency::minor_unit_exponent($code)', $renderer);

        $edit = file_get_contents($root . $files[1]);
        self::assertStringContainsString('CommerceCurrencyAmount::major_input_from_minor', $edit);
        self::assertStringContainsString('CommerceCurrencyLabelFormatter::format($currency)', $edit);

        $view = file_get_contents($root . $files[2]);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $view);
        self::assertStringContainsString('Currency::visual_marker', $view);
    }
}
