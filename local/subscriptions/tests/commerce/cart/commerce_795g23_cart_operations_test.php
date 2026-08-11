<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cart;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\ownership\CommerceCartOwnershipGateway;
use local_subscriptions\commerce\cart\policy\CommerceRangeQuantityPolicy;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\domain\value\CommerceMoney;

final class commerce_795g23_cart_operations_test extends \advanced_testcase {
    public function test_single_quantity_product_is_not_incremented_twice(): void {
        $service = $this->service();
        $first = $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);
        $second = $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);

        $this->assertTrue($first->has_changed());
        $this->assertFalse($second->has_changed());
        $this->assertSame('already_in_cart', $second->get_messages()[0]->get_code());
        $this->assertSame(1, $second->get_cart()->get_items()[0]->get_quantity());
    }

    public function test_range_quantity_product_can_be_incremented_and_updated(): void {
        $service = $this->service();
        $service->add_product(96, 'EUR', 'fr', 'BOOK', 20, 2);
        $result = $service->add_product(96, 'EUR', 'fr', 'BOOK', 20, 2);
        $updated = $service->update_quantity(96, 'EUR', 'fr', 'BOOK', 20, 5);

        $this->assertSame(4, $result->get_cart()->get_items()[0]->get_quantity());
        $this->assertSame(5, $updated->get_cart()->get_items()[0]->get_quantity());
    }

    public function test_owned_product_is_refused_before_storage(): void {
        $ownership = new class implements CommerceCartOwnershipGateway {
            public function owns(int $customerid, string $productsku): bool {
                return $productsku === 'COURSE-A1';
            }
        };
        $service = $this->service($ownership);
        $result = $service->add_product(96, 'EUR', 'fr', 'COURSE-A1', 10);

        $this->assertFalse($result->has_changed());
        $this->assertTrue($result->get_cart()->is_empty());
        $this->assertSame('already_owned', $result->get_messages()[0]->get_code());
    }

    public function test_remove_and_clear_are_idempotent(): void {
        $service = $this->service();
        $service->add_product(96, 'EUR', 'fr', 'BOOK', 20, 2);
        $removed = $service->remove_product(96, 'EUR', 'BOOK', 20);
        $removedagain = $service->remove_product(96, 'EUR', 'BOOK', 20);
        $cleared = $service->clear_cart(96, 'EUR');

        $this->assertTrue($removed->has_changed());
        $this->assertFalse($removedagain->has_changed());
        $this->assertFalse($cleared->has_changed());
    }

    public function test_calculator_reprices_and_returns_complete_totals(): void {
        $service = $this->service();
        $service->add_product(96, 'EUR', 'fr', 'BOOK', 20, 3);
        $snapshot = $service->snapshot(96, 'EUR', 'fr', 1234567890);

        $this->assertSame(4500, $snapshot->get_totals()->get_subtotal()->get_amount_minor());
        $this->assertSame(0, $snapshot->get_totals()->get_discount()->get_amount_minor());
        $this->assertSame(0, $snapshot->get_totals()->get_tax()->get_amount_minor());
        $this->assertSame(4500, $snapshot->get_totals()->get_total()->get_amount_minor());
        $this->assertSame([], $snapshot->get_messages());
    }

    private function service(?CommerceCartOwnershipGateway $ownership = null): CommerceCartService {
        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(string $productsku, int $priceid, string $currency, string $language, ?int $at = null): CommerceCartProductQuote {
                $sku = strtoupper($productsku);
                return new CommerceCartProductQuote(
                    $sku,
                    $priceid,
                    $sku,
                    CommerceMoney::from_minor($sku === 'BOOK' ? 1500 : 25000, $currency),
                    $sku === 'BOOK' ? new CommerceRangeQuantityPolicy(1, 10, 1) : new CommerceSingleQuantityPolicy()
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog),
            $catalog,
            $ownership
        );
    }
}
