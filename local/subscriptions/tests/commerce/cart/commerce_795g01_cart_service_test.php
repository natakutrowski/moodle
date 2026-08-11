<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_795g01_cart_service_test extends \advanced_testcase {
    public function test_open_reuses_the_active_cart_for_customer_and_currency(): void {
        $service = $this->service();

        $first = $service->open(96, 'EUR');
        $second = $service->open(96, 'eur');

        $this->assertSame($first->get_uuid(), $second->get_uuid());
    }

    public function test_snapshot_resolves_current_price_and_totals(): void {
        $service = $this->service();
        $cart = $service->open(96, 'EUR')->with_items([
            new CommerceCartItem('COURSE-A1', 10, 1),
        ]);
        $service->save($cart);

        $snapshot = $service->snapshot(96, 'EUR', 'fr', 1234567890);

        $this->assertCount(1, $snapshot->get_items());
        $this->assertSame(25000, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        $this->assertSame(25000, $snapshot->get_totals()->get_total()->get_amount_minor());
        $this->assertSame(1, $snapshot->get_items()[0]->get_maximum_quantity());
    }

    public function test_clear_removes_the_session_cart_slot(): void {
        $service = $this->service();
        $first = $service->open(96, 'EUR');

        $service->clear(96, 'EUR');
        $second = $service->open(96, 'EUR');

        $this->assertNotSame($first->get_uuid(), $second->get_uuid());
    }

    private function service(): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                return new CommerceCartProductQuote(
                    strtoupper($productsku),
                    $priceid,
                    'Cours A1',
                    CommerceMoney::from_minor(25000, $currency),
                    new CommerceSingleQuantityPolicy()
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog)
        );
    }
}
