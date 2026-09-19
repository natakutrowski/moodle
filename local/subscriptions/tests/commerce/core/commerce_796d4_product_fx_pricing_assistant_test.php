<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\currency\CommerceFxPriceSuggestionService;
use local_subscriptions\commerce\currency\CommerceFxRateBook;
use local_subscriptions\commerce\currency\CommerceProductFxSuggestionService;
use local_subscriptions\commerce\domain\value\CommerceMoney;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d4_product_fx_pricing_assistant_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('commerce_enabled_currencies', 'EUR,USD,JPY,TND', 'local_subscriptions');
    }

    public function test_assistant_only_suggests_missing_prices_with_available_rates(): void {
        $registry = new CommerceCurrencyRegistry();
        $book = new CommerceFxRateBook($registry);
        $book->save('EUR', [
            'USD' => '1.20',
            'JPY' => '170',
            // TND deliberately has no rate.
        ], 'TU');

        $service = new CommerceProductFxSuggestionService(
            $book,
            new CommerceFxPriceSuggestionService($book)
        );

        $prices = [
            new CommerceProductPrice(
                'SKU.TEST',
                CommerceMoney::from_minor(3000, 'EUR'),
                true
            ),
            new CommerceProductPrice(
                'SKU.TEST',
                CommerceMoney::from_minor(3900, 'USD'),
                true
            ),
        ];

        $result = $service->suggest_missing(
            $prices,
            $registry->enabled(),
            'none'
        );

        $this->assertSame('EUR', $result['sourcecurrency']);
        $this->assertSame(3000, $result['sourceamountminor']);
        $this->assertArrayNotHasKey('USD', $result['suggestions']);
        $this->assertArrayHasKey('JPY', $result['suggestions']);
        $this->assertSame(5100, $result['suggestions']['JPY']['suggestedminor']);
        $this->assertSame(['TND'], $result['missingrates']);
    }

    public function test_assistant_requires_a_price_in_fx_base_currency(): void {
        $registry = new CommerceCurrencyRegistry();
        $book = new CommerceFxRateBook($registry);
        $book->save('EUR', ['JPY' => '170'], 'TU');

        $service = new CommerceProductFxSuggestionService(
            $book,
            new CommerceFxPriceSuggestionService($book)
        );

        $this->expectException(\coding_exception::class);
        $service->suggest_missing([
            new CommerceProductPrice(
                'SKU.TEST',
                CommerceMoney::from_minor(4000, 'USD'),
                true
            ),
        ], $registry->enabled());
    }

    public function test_d4_restores_fx_back_navigation_and_keeps_preview_non_authoritative(): void {
        global $CFG;

        $currencies = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/currencies.php'
        );
        $prices = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/prices.php'
        );

        $this->assertStringContainsString('commerce_fx_back_to_localisation', $currencies);
        $this->assertStringContainsString("action', 'value' => 'fxpreview'", $prices);
        $this->assertStringContainsString("action', 'value' => 'fxapply'", $prices);
        $this->assertStringContainsString('price_currency_exists', $prices);
    }

    public function test_integer_fx_rate_is_not_truncated(): void {
        $registry = new CommerceCurrencyRegistry();
        $book = new CommerceFxRateBook($registry);
        $book->save('EUR', [
            'JPY' => '170',
        ], 'TU');

        $this->assertSame('170', $book->rate_for('JPY'));

        $service = new CommerceFxPriceSuggestionService($book);
        $suggestion = $service->suggest(3000, 'EUR', 'JPY', 'none');
        $this->assertSame(5100, $suggestion['suggestedminor']);
    }


}
