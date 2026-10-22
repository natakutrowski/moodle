<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\currency\CommerceFxRateBook;
use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\donation\CommerceDonationUnitSuggestionService;
use local_subscriptions\commerce\domain\value\CommerceMoney;

/** 7.98B.2 — currency-generic free Donation amounts. */
final class commerce_798b2_donation_free_amount_test extends \advanced_testcase {
    private const SKU = 'DONATION.ALF.B2';

    public function test_policy_uses_integer_units_and_catalogue_unit_price(): void {
        $this->resetAfterTest(true);
        $policy = new CommerceDonationAmountPolicy();

        self::assertSame(1, $policy->require_units('1'));
        self::assertSame(25, $policy->require_units(25));
        self::assertSame(2500, $policy->amount(CommerceMoney::from_minor(100, 'EUR'), 25)->get_amount_minor());
        self::assertSame(250000, $policy->amount(CommerceMoney::from_minor(10000, 'RUB'), 25)->get_amount_minor());
        self::assertSame(2500, $policy->amount(CommerceMoney::from_minor(100, 'JPY'), 25)->get_amount_minor());
    }

    public function test_policy_rejects_zero_fractional_and_over_limit_units(): void {
        $this->resetAfterTest(true);
        set_config('commerce_donation_max_units', 50, 'local_subscriptions');
        $policy = new CommerceDonationAmountPolicy();

        foreach ([0, -1, '2.5', '01', 51] as $invalid) {
            try {
                $policy->require_units($invalid);
                self::fail('Expected invalid Donation units to be rejected.');
            } catch (\coding_exception) {
                self::assertTrue(true);
            }
        }
    }

    public function test_fx_helper_suggests_readable_power_of_ten_without_becoming_authoritative(): void {
        $this->resetAfterTest(true);
        set_config(
            'commerce_enabled_currencies',
            'EUR,USD,SEK,RUB,JPY,KRW',
            'local_subscriptions'
        );
        $book = new CommerceFxRateBook();
        $book->save('EUR', [
            'USD' => '1.08',
            'SEK' => '11.2',
            'RUB' => '105',
            'JPY' => '170',
            'KRW' => '1450',
        ], 'test');
        $helper = new CommerceDonationUnitSuggestionService($book);

        self::assertSame(1, $helper->suggest_major_unit('EUR'));
        self::assertSame(1, $helper->suggest_major_unit('USD'));
        self::assertSame(10, $helper->suggest_major_unit('SEK'));
        self::assertSame(100, $helper->suggest_major_unit('RUB'));
        self::assertSame(100, $helper->suggest_major_unit('JPY'));
        self::assertSame(1000, $helper->suggest_major_unit('KRW'));
    }

    public function test_adding_same_donation_replaces_units_and_keeps_quantity_one(): void {
        $this->resetAfterTest(true);
        $gateway = $this->gateway(['EUR' => 100]);
        $service = $this->cart_service($gateway);

        $first = $service->add_product(
            42,
            'EUR',
            'fr',
            self::SKU,
            10,
            1,
            [CommerceDonationAmountPolicy::METADATA_UNITS => 10]
        );
        self::assertTrue($first->has_changed());

        $second = $service->add_product(
            42,
            'EUR',
            'fr',
            self::SKU,
            10,
            1,
            [CommerceDonationAmountPolicy::METADATA_UNITS => 25]
        );
        self::assertTrue($second->has_changed());
        self::assertCount(1, $second->get_cart()->get_items());
        $item = $second->get_cart()->get_items()[0];
        self::assertSame(1, $item->get_quantity());
        self::assertSame(25, $item->get_metadata()[CommerceDonationAmountPolicy::METADATA_UNITS]);

        $snapshot = (new CommerceCartCalculator($gateway))->calculate($second->get_cart(), 'fr');
        self::assertSame(2500, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        self::assertSame(2500, $snapshot->get_totals()->get_total()->get_amount_minor());
    }

    public function test_invalid_donation_units_do_not_mutate_cart(): void {
        $this->resetAfterTest(true);
        set_config('commerce_donation_max_units', 10, 'local_subscriptions');
        $gateway = $this->gateway(['EUR' => 100]);
        $service = $this->cart_service($gateway);

        $result = $service->add_product(
            42,
            'EUR',
            'fr',
            self::SKU,
            10,
            1,
            [CommerceDonationAmountPolicy::METADATA_UNITS => 11]
        );
        self::assertFalse($result->has_changed());
        self::assertTrue($result->get_cart()->is_empty());
        self::assertSame('donation_amount_invalid', $result->get_messages()[0]->get_code());
    }

    public function test_same_unit_intent_reprices_from_target_currency_catalogue_unit(): void {
        $this->resetAfterTest(true);
        $gateway = $this->gateway([
            'EUR' => 100,      // 1 EUR.
            'RUB' => 10000,    // 100 RUB.
            'JPY' => 100,      // 100 JPY (zero-decimal currency).
            'KRW' => 1000,     // 1000 KRW (zero-decimal currency).
        ]);
        $metadata = [CommerceDonationAmountPolicy::METADATA_UNITS => 10];

        foreach ([
            'EUR' => 1000,
            'RUB' => 100000,
            'JPY' => 1000,
            'KRW' => 10000,
        ] as $currency => $expectedminor) {
            $uuid = match ($currency) {
                'EUR' => str_repeat('a', 32),
                'RUB' => str_repeat('b', 32),
                'JPY' => str_repeat('c', 32),
                'KRW' => str_repeat('d', 32),
            };
            $cart = new CommerceCart(
                $uuid,
                42,
                $currency,
                [new CommerceCartItem(self::SKU, 10, 1, $metadata)],
                [],
                1,
                1
            );
            $snapshot = (new CommerceCartCalculator($gateway))->calculate($cart, 'fr');
            self::assertSame($expectedminor, $snapshot->get_totals()->get_total()->get_amount_minor(), $currency);
        }
    }

    private function cart_service(CommerceCartCatalogGateway $gateway): CommerceCartService {
        $repository = new CommerceInMemoryCartRepository();
        return new CommerceCartService(
            $repository,
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($gateway),
            $gateway
        );
    }

    /** @param array<string,int> $minorbycurrency */
    private function gateway(array $minorbycurrency): CommerceCartCatalogGateway {
        return new class($minorbycurrency) implements CommerceCartCatalogGateway {
            public function __construct(private readonly array $minorbycurrency) {
            }

            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                $currency = strtoupper(trim($currency));
                if (!isset($this->minorbycurrency[$currency])) {
                    throw new \coding_exception('Missing test Donation price.');
                }
                return new CommerceCartProductQuote(
                    strtoupper(trim($productsku)),
                    $priceid,
                    'Donation B2',
                    CommerceMoney::from_minor($this->minorbycurrency[$currency], $currency),
                    new CommerceSingleQuantityPolicy(),
                    CommerceProductType::DONATION
                );
            }
        };
    }
}
