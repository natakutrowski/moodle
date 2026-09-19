<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\currency\CommerceFxPriceSuggestionService;
use local_subscriptions\commerce\currency\CommerceFxRateBook;

final class commerce_796d_currency_fx_test extends \advanced_testcase {
    protected function tearDown(): void {
        unset_config('commerce_enabled_currencies', 'local_subscriptions');
        unset_config('commerce_fx_base_currency', 'local_subscriptions');
        unset_config('commerce_fx_rates_json', 'local_subscriptions');
        parent::tearDown();
    }

    public function test_rate_book_keeps_enabled_currency_boundary(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');
        $book = new CommerceFxRateBook(new CommerceCurrencyRegistry());
        $book->save('EUR', ['JPY' => '171.25', 'TND' => '3.365', 'USD' => '1.15'], 'manual-test');
        $this->assertSame('EUR', $book->base_currency());
        $this->assertSame('171.25', $book->rate_for('JPY'));
        $this->assertSame('3.365', $book->rate_for('TND'));
        $this->assertNull($book->rate_for('USD'));
        $this->assertSame('manual-test', $book->rates()['JPY']['source']);
    }

    public function test_suggestions_respect_target_minor_units(): void {
        $this->resetAfterTest();
        set_config('commerce_enabled_currencies', 'EUR,JPY,TND', 'local_subscriptions');
        $book = new CommerceFxRateBook(new CommerceCurrencyRegistry());
        $book->save('EUR', ['JPY' => '171.25', 'TND' => '3.365']);
        $service = new CommerceFxPriceSuggestionService($book);
        $jpy = $service->suggest(3000, 'EUR', 'JPY');
        $this->assertSame(5138, $jpy['suggestedminor']);
        $tnd = $service->suggest(3000, 'EUR', 'TND');
        $this->assertSame(100950, $tnd['suggestedminor']);
        $rounded = $service->suggest(3000, 'EUR', 'TND', 'ending90');
        $this->assertSame(100900, $rounded['suggestedminor']);
    }

}
