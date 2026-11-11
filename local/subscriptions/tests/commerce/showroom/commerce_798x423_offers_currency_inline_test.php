<?php

declare(strict_types=1);
namespace local_subscriptions;

use advanced_testcase;

/** Currency dropdown is a compact part of the first offer heading only. @coversNothing */
final class commerce_798x423_offers_currency_inline_test extends advanced_testcase {
    public function test_currency_select_is_in_first_card_without_changing_checkout(): void {
        $base = dirname(__DIR__, 3) . '/designs/articles_alf_reimport/dist/';
        $js = (string) file_get_contents($base . 'main.js');
        $css = (string) file_get_contents($base . 'style.css');
        self::assertSame(3, substr_count($js, 'currencySelector:s.jsxDEV(S5'));
        self::assertSame(0, substr_count($js, 'campusfr-v3-offer-head'));
        self::assertStringNotContainsString('relative z-10 mx-auto mt-9 flex max-w-6xl justify-center px-2 sm:justify-end', $js);
        self::assertStringContainsString('onChange:j=>{A(j.currentTarget.value)}', $js);
        self::assertStringContainsString('data-campusfr-native-checkout-v46', $js);
        self::assertStringContainsString('CampusFR offers currency selector: compact first-card heading', $css);
        self::assertStringContainsString('CampusFR: three synced currency selectors under offers', $css);
        self::assertStringContainsString('focus-visible', $css);
    }
}
