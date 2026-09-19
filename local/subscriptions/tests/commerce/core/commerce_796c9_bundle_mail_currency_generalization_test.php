<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferPricingPresentationBuilder;
use local_subscriptions\currency\CurrencyFormatter;

/** Tests 7.96C9 Bundle pricing and Personal Offer mail currency generalization. */
final class commerce_796c9_bundle_mail_currency_generalization_test extends advanced_testcase {
    public function test_personal_offer_pricing_builder_preserves_currency_minor_units(): void {
        $this->resetAfterTest();

        $pricing = CommercePersonalOfferPricingPresentationBuilder::build([
            'JPY' => ['regularminor' => 4490, 'offerminor' => 3990],
            'TND' => ['regularminor' => 14925, 'offerminor' => 12925],
        ], 'TND');

        self::assertSame('TND', $pricing['currency']);
        self::assertSame(CurrencyFormatter::format_minor(3990, 'JPY'), $pricing['prices'][0]['offerformatted']);
        self::assertSame(CurrencyFormatter::format_minor(12925, 'TND'), $pricing['prices'][1]['offerformatted']);
    }

    public function test_c9_surfaces_use_currency_core_boundaries(): void {
        $root = dirname(__DIR__, 3);

        $pricing = file_get_contents($root . '/admin/commerce/products/pricing.php');
        self::assertIsString($pricing);
        self::assertStringContainsString('CommerceCurrencyAmount::from_major_input', $pricing);
        self::assertStringContainsString('CommerceCurrencyAmount::major_input_from_minor', $pricing);
        self::assertStringContainsString('CommerceCurrencyRegistry', $pricing);
        self::assertStringContainsString('CurrencyFormatter::format_minor_code', $pricing);
        self::assertStringNotContainsString('$regularminor / 100', $pricing);
        self::assertStringNotContainsString('((float)$rawpromo) * 100', $pricing);

        $mailtemplate = file_get_contents($root . '/classes/commerce/mail/template/CommercePersonalOfferTemplate.php');
        self::assertIsString($mailtemplate);
        self::assertStringContainsString('CurrencyFormatter::format_minor_number', $mailtemplate);
        self::assertStringContainsString('Currency::visual_marker', $mailtemplate);
        self::assertStringNotContainsString("['EUR' => '€', 'RUB' => '₽'", $mailtemplate);

        $preview = file_get_contents(
            $root
            . '/classes/commerce/personaloffer/mail/'
            . 'CommercePersonalOfferCampaignMailPreviewService.php'
        );
        self::assertIsString($preview);
        self::assertStringContainsString(
            'CommerceCurrencyRegistry',
            $preview
        );
        self::assertStringContainsString(
            'CommercePersonalOfferPricingPresentationBuilder::build(',
            $preview
        );
        self::assertStringContainsString(
            "\$preferredcurrency = \$language === 'ru' ? 'RUB' : 'EUR';",
            $preview
        );
        self::assertStringContainsString(
            'if (!isset($available[$preferredcurrency]))',
            $preview
        );
        self::assertStringContainsString(
            '$preferredcurrency = (string)array_key_first($available);',
            $preview
        );
    }
}
