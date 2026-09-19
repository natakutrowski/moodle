<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\domain\CommerceCartMessage;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k3_cart_reservation_bridge_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;

        return (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(
            new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            )
        );
    }

    private function setup_offer(
        string $sku,
        int $now
    ): CommerceProduct {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($sku),
                $sku,
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                null,
                null,
                $now,
                null,
                1,
                null,
                null,
                $now,
                $now
            )
        );

        $product = $this->product($sku);

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                1,
                null,
                null,
                $now,
                $now
            )
        );

        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            $sku . ' group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        return $product;
    }

    private function carts(): CommerceCartService {
        global $DB;

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
                    strtoupper($productsku),
                    CommerceMoney::from_minor(100, $currency),
                    new CommerceSingleQuantityPolicy()
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog),
            $catalog,
            null,
            null,
            null,
            null,
            CommercePedagogicalSeatReservationService::create($DB)
        );
    }

    public function test_add_to_cart_holds_last_seat_and_second_cart_is_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K3-LAST', $now);
        $carts = $this->carts();

        $first = $carts->add_product(
            101,
            'EUR',
            'fr',
            'K3-LAST',
            1,
            1,
            [],
            $now
        );
        self::assertTrue($first->has_changed());

        self::assertSame(
            0,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K3-LAST', $now)
                ->get_remaining()
        );

        $second = $carts->add_product(
            102,
            'EUR',
            'fr',
            'K3-LAST',
            1,
            1,
            [],
            $now
        );

        self::assertFalse($second->has_changed());
        self::assertCount(1, $second->get_messages());
        self::assertContains(
            $second->get_messages()[0]->get_code(),
            [
                CommercePedagogicalCapacityService::PROMOTION_FULL,
                CommercePedagogicalCapacityService::OFFER_FULL,
                CommercePedagogicalCapacityService::GROUP_FULL,
            ]
        );
    }

    public function test_remove_product_releases_hold_immediately(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K3-REMOVE', $now);
        $carts = $this->carts();

        $added = $carts->add_product(
            201, 'EUR', 'fr', 'K3-REMOVE', 2, 1, [], $now
        );
        self::assertTrue($added->has_changed());

        $removed = $carts->remove_product(
            201, 'EUR', 'K3-REMOVE', 2
        );
        self::assertTrue($removed->has_changed());

        self::assertSame(
            1,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K3-REMOVE', $now + 1)
                ->get_remaining()
        );
    }

    public function test_clear_cart_releases_all_holds(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K3-CLEAR', $now);
        $carts = $this->carts();

        $added = $carts->add_product(
            301, 'EUR', 'fr', 'K3-CLEAR', 3, 1, [], $now
        );
        self::assertTrue($added->has_changed());

        $cleared = $carts->clear_cart(301, 'EUR');
        self::assertTrue($cleared->has_changed());

        self::assertSame(
            1,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K3-CLEAR', $now + 1)
                ->get_remaining()
        );
    }

    public function test_buy_now_uses_isolated_cart_and_holds_seat(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K3-DIRECT', $now);
        $carts = $this->carts();

        $prepared = $carts->prepare_direct_product(
            401,
            'EUR',
            'fr',
            'K3-DIRECT',
            4,
            1,
            [],
            $now
        );

        self::assertTrue($prepared->has_changed());
        self::assertTrue(
            $prepared->get_cart()->get_metadata()['purchase_flow'] === 'direct'
        );

        self::assertSame(
            0,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K3-DIRECT', $now)
                ->get_remaining()
        );

        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    CommercePedagogicalCapacityService::create($DB)
                        ->for_product(
                            'K3-DIRECT',
                            $now,
                            $prepared->get_cart()->get_uuid()
                        )
                        ->get_promotion_id(),
                    CommercePedagogicalCapacityService::create($DB)
                        ->for_product(
                            'K3-DIRECT',
                            $now,
                            $prepared->get_cart()->get_uuid()
                        )
                        ->get_product_id(),
                    $prepared->get_cart()->get_uuid()
                );

        self::assertNotNull($reservation);
    }

    public function test_classic_product_cart_behaviour_is_unchanged(): void {
        $this->resetAfterTest(true);
        $now = time();
        $this->product('K3-CLASSIC');
        $carts = $this->carts();

        $result = $carts->add_product(
            501,
            'EUR',
            'fr',
            'K3-CLASSIC',
            5,
            1,
            [],
            $now
        );

        self::assertTrue($result->has_changed());
        self::assertCount(1, $result->get_cart()->get_items());
    }
}
