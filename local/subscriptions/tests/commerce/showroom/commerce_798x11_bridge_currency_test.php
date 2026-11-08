<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCurrencyOptions;
use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeOfferContract;
use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;

final class commerce_798x11_bridge_currency_test extends \advanced_testcase {
    public function test_currency_selector_preserves_preview_id_and_filters_invalid_choices(): void {
        $baseurl = new \moodle_url('/local/subscriptions/admin/commerce/showrooms/preview.php', [
            'id' => 6, 'currency' => 'EUR',
        ]);
        $options = CommerceShowroomBridgeCurrencyOptions::from_showroom_page([
            ['value' => 'EUR', 'label' => '🇪🇺 EUR'],
            ['value' => 'RUB', 'label' => '🇷🇺 RUB'],
            ['value' => 'RUB', 'label' => 'duplicate'],
            ['value' => 'EUR<script>', 'label' => 'invalid'],
        ], 'RUB', $baseurl);
        self::assertCount(2, $options);
        self::assertSame('EUR', $options[0]['value']);
        self::assertFalse($options[0]['selected']);
        self::assertTrue($options[1]['selected']);
        self::assertStringContainsString('id=6', $options[1]['url']);
        self::assertStringContainsString('currency=RUB', $options[1]['url']);
        self::assertSame('🇷🇺 RUB', $options[1]['label']);
    }

    public function test_donation_units_and_presets_remain_defined_by_commerce(): void {
        $this->resetAfterTest(true);
        set_config('commerce_donation_max_units', 100, 'local_subscriptions');
        $policy = new CommerceDonationAmountPolicy();
        self::assertSame(1, $policy->require_units('1'));
        self::assertSame(10, $policy->require_units('10'));
        self::assertContains(10, $policy->preset_units());
        $rows = CommerceShowroomBridgeOfferContract::expose([[
            'role' => 'donation', 'currency' => 'RUB', 'priceid' => 42, 'amountminor' => 10000,
            'donationmaxunits' => $policy->max_units(), 'donationdefaultunits' => 10,
            'donationpresets' => array_map(static fn($u) => ['units' => $u], $policy->preset_units()),
        ]]);
        self::assertSame(10000, $rows[0]['amountminor']);
        self::assertSame('RUB', $rows[0]['currency']);
        self::assertSame(10, $rows[0]['donationdefaultunits']);
        self::assertSame($policy->max_units(), $rows[0]['donationmaxunits']);
    }
}
