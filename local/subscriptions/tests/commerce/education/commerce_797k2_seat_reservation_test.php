<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k2_seat_reservation_test extends advanced_testcase {
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

    private function promotion(
        int $courseid,
        string $key,
        int $now,
        ?int $capacity
    ): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                $key,
                $courseid,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                null,
                null,
                $now,
                null,
                $capacity,
                null,
                null,
                $now,
                $now
            )
        );
    }

    /**
     * @return array{
     *   promotion: CommercePedagogicalPromotion,
     *   product: CommerceProduct
     * }
     */
    private function setup_offer(
        string $sku,
        int $now,
        int $promotioncapacity = 10,
        int $offercapacity = 10,
        int $groupsize = 10
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            strtolower($sku),
            $now,
            $promotioncapacity
        );
        $product = $this->product($sku);

        CommercePedagogicalPromotionOfferRepository::create($DB)
            ->link(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                $offercapacity,
                null,
                $now
            );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                $groupsize,
                null,
                null,
                $now,
                $now
            )
        );

        CommercePedagogicalGroupOrchestrator::create($DB)
            ->create_group(
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

        return [
            'promotion' => $promotion,
            'product' => $product,
        ];
    }

    public function test_last_seat_is_held_for_one_cart_and_hidden_from_others(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $setup = $this->setup_offer(
            'K2-LAST',
            $now,
            1,
            1,
            1
        );

        $service =
            CommercePedagogicalSeatReservationService::create($DB);
        $capacity =
            CommercePedagogicalCapacityService::create($DB);

        $cart1 = str_repeat('a', 32);
        $cart2 = str_repeat('b', 32);

        $hold = $service->reserve(
            'K2-LAST',
            $cart1,
            101,
            1,
            $now
        );

        self::assertNotNull($hold);
        self::assertSame(
            CommercePedagogicalSeatReservation::ACTIVE,
            $hold->get_state()
        );
        self::assertSame(
            $now + CommercePedagogicalSeatReservationService::DEFAULT_TTL,
            $hold->get_expires_at()
        );

        $public = $capacity->for_product(
            'K2-LAST',
            $now
        );
        self::assertSame(1, $public->get_offer_reserved_quantity());
        self::assertSame(1, $public->get_promotion_reserved_quantity());
        self::assertSame(0, $public->get_remaining());
        self::assertTrue($public->is_sold_out());

        try {
            $service->reserve(
                'K2-LAST',
                $cart2,
                102,
                1,
                $now
            );
            self::fail('The second cart must not obtain the last seat.');
        } catch (CommercePedagogicalSeatReservationException $e) {
            self::assertContains(
                $e->get_code_key(),
                [
                    CommercePedagogicalCapacityService::PROMOTION_FULL,
                    CommercePedagogicalCapacityService::OFFER_FULL,
                    CommercePedagogicalCapacityService::GROUP_FULL,
                ]
            );
        }
    }

    public function test_same_cart_can_renew_without_counting_its_hold_twice(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $this->setup_offer(
            'K2-RENEW',
            $now,
            1,
            1,
            1
        );

        $service =
            CommercePedagogicalSeatReservationService::create($DB);
        $cart = str_repeat('c', 32);

        $first = $service->reserve(
            'K2-RENEW',
            $cart,
            201,
            1,
            $now,
            60
        );
        $renewed = $service->renew(
            'K2-RENEW',
            $cart,
            201,
            1,
            $now + 30,
            120
        );

        self::assertSame($first?->get_id(), $renewed?->get_id());
        self::assertSame($now + 150, $renewed?->get_expires_at());

        $repository =
            CommercePedagogicalSeatReservationRepository::create($DB);

        self::assertSame(
            1,
            $repository->active_quantity_for_offer(
                (int)$renewed->get_promotion_id(),
                (int)$renewed->get_product_id(),
                $now + 30
            )
        );
    }

    public function test_expiry_restores_capacity_without_manual_cleanup(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $this->setup_offer(
            'K2-EXPIRY',
            $now,
            1,
            1,
            1
        );

        $service =
            CommercePedagogicalSeatReservationService::create($DB);
        $capacity =
            CommercePedagogicalCapacityService::create($DB);

        $service->reserve(
            'K2-EXPIRY',
            str_repeat('d', 32),
            301,
            1,
            $now,
            30
        );

        self::assertSame(
            0,
            $capacity->for_product(
                'K2-EXPIRY',
                $now + 10
            )->get_remaining()
        );

        // Expired rows are ignored by the capacity query immediately,
        // even before lazy state cleanup is run.
        self::assertSame(
            1,
            $capacity->for_product(
                'K2-EXPIRY',
                $now + 31
            )->get_remaining()
        );

        self::assertSame(
            1,
            $service->expire_due($now + 31)
        );
    }

    public function test_release_and_consume_end_active_capacity_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $setup = $this->setup_offer(
            'K2-LIFECYCLE',
            $now,
            2,
            2,
            2
        );

        $service =
            CommercePedagogicalSeatReservationService::create($DB);
        $repository =
            CommercePedagogicalSeatReservationRepository::create($DB);

        $releasedcart = str_repeat('e', 32);
        $consumedcart = str_repeat('f', 32);

        $service->reserve(
            'K2-LIFECYCLE',
            $releasedcart,
            401,
            1,
            $now
        );
        self::assertTrue(
            $service->release_product(
                'K2-LIFECYCLE',
                $releasedcart,
                $now + 1
            )
        );

        $released = $repository->find(
            (int)$setup['promotion']->get_id(),
            (int)$setup['product']->get_id(),
            $releasedcart
        );
        self::assertSame(
            CommercePedagogicalSeatReservation::RELEASED,
            $released?->get_state()
        );

        $service->reserve(
            'K2-LIFECYCLE',
            $consumedcart,
            402,
            1,
            $now + 2
        );
        self::assertTrue(
            $service->consume(
                'K2-LIFECYCLE',
                $consumedcart,
                'CFR-K2-0001',
                $now + 3
            )
        );

        $consumed = $repository->find(
            (int)$setup['promotion']->get_id(),
            (int)$setup['product']->get_id(),
            $consumedcart
        );
        self::assertSame(
            CommercePedagogicalSeatReservation::CONSUMED,
            $consumed?->get_state()
        );
        self::assertSame(
            'CFR-K2-0001',
            $consumed?->get_purchase_reference()
        );
    }

    public function test_ru_and_fr_holds_compete_for_global_capacity_but_not_offer_capacity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'k2-shared-promo',
            $now,
            2
        );
        $ru = $this->product('K2-RU');
        $fr = $this->product('K2-FR');

        $offers =
            CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            2,
            null,
            $now
        );
        $offers->link(
            (int)$promotion->get_id(),
            (int)$fr->get_id(),
            2,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                2,
                null,
                null,
                $now,
                $now
            )
        );

        $orchestrator =
            CommercePedagogicalGroupOrchestrator::create($DB);
        $orchestrator->create_group(
            (int)$promotion->get_id(),
            'RU',
            0,
            null,
            'ru',
            null,
            null,
            null,
            $now,
            null,
            (int)$ru->get_id()
        );
        $orchestrator->create_group(
            (int)$promotion->get_id(),
            'FR',
            1,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$fr->get_id()
        );

        $service =
            CommercePedagogicalSeatReservationService::create($DB);
        $capacity =
            CommercePedagogicalCapacityService::create($DB);

        $service->reserve(
            'K2-RU',
            str_repeat('1', 32),
            501,
            1,
            $now
        );

        $rusnapshot = $capacity->for_product('K2-RU', $now);
        $frsnapshot = $capacity->for_product('K2-FR', $now);

        self::assertSame(1, $rusnapshot->get_offer_reserved_quantity());
        self::assertSame(1, $rusnapshot->get_offer_remaining());
        self::assertSame(0, $frsnapshot->get_offer_reserved_quantity());
        self::assertSame(2, $frsnapshot->get_offer_remaining());

        // The same RU hold consumes one place from the common promotion.
        self::assertSame(1, $frsnapshot->get_promotion_reserved_quantity());
        self::assertSame(1, $frsnapshot->get_promotion_remaining());
        self::assertSame(1, $frsnapshot->get_remaining());
    }

    public function test_unlinked_product_requires_no_reservation(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $this->product('K2-CLASSIC');

        self::assertNull(
            CommercePedagogicalSeatReservationService::create($DB)
                ->reserve(
                    'K2-CLASSIC',
                    str_repeat('9', 32),
                    0,
                    1,
                    $now
                )
        );
    }
}
