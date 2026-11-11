<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;

/** Validate the three checkout-adjacent currency selectors. @coversNothing */
final class commerce_798x424_offers_currency_triple_test extends advanced_testcase {
    public function test_currency_selectors_share_one_bridge_state(): void {
        $dist = dirname(__DIR__, 3) . '/designs/articles_alf_reimport/dist/';
        $js = (string)file_get_contents($dist . 'main.js');
        $css = (string)file_get_contents($dist . 'style.css');
        self::assertSame(3, substr_count($js, 'currencySelector:s.jsxDEV(S5'));
        self::assertSame(1, substr_count($js, 'slot:"course"'));
        self::assertSame(1, substr_count($js, 'slot:"bundle"'));
        self::assertSame(1, substr_count($js, 'slot:"donation"'));
        self::assertStringContainsString('id:"campusfr-v3-offer-currency-"+__cfSlot', $js);
        self::assertStringContainsString('changeCurrency:A', $js);
        self::assertStringContainsString('data-campusfr-native-checkout-v46', $js);
        self::assertStringContainsString('CampusFR: three synced currency selectors under offers', $css);
        self::assertStringNotContainsString('relative z-10 mx-auto mt-9 flex max-w-6xl justify-center px-2 sm:justify-end', $js);
    }
}
