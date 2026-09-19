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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797m1_dynamic_capacity_and_sales_window_test extends advanced_testcase {
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
        ?int $capacity,
        string $status = CommercePedagogicalPromotionStatus::OPEN,
        ?int $salesopensat = null,
        ?int $salesclosesat = null,
        ?int $startsat = null
    ): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                $key,
                $courseid,
                $status,
                true,
                $salesopensat,
                $salesclosesat,
                $startsat ?? $now,
                null,
                $capacity,
                null,
                null,
                $now,
                $now
            )
        );
    }

    private function update_promotion(
        CommercePedagogicalPromotion $promotion,
        int $now,
        ?int $capacity = null,
        ?string $status = null,
        ?int $salesopensat = null,
        ?int $salesclosesat = null,
        ?int $startsat = null
    ): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                $promotion->get_id(),
                $promotion->get_promotion_key(),
                $promotion->get_name(),
                $promotion->get_course_id(),
                $status ?? $promotion->get_status(),
                $promotion->is_published(),
                $salesopensat ?? $promotion->get_sales_opens_at(),
                $salesclosesat ?? $promotion->get_sales_closes_at(),
                $startsat ?? $promotion->get_starts_at(),
                $promotion->get_ends_at(),
                $capacity,
                $promotion->get_created_by(),
                $promotion->get_modified_by(),
                $promotion->get_time_created(),
                $now
            )
        );
    }

    private function cart_uuid(string $seed): string {
        return substr(hash('sha256', $seed), 0, 32);
    }

    public function test_live_global_capacity_increase_reopens_both_offers_without_touching_existing_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'm1-global-live',
            $now,
            2
        );
        $ru = $this->product('M1-GLOBAL-RU');
        $fr = $this->product('M1-GLOBAL-FR');

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$promotion->get_id(), (int)$ru->get_id(), 2, null, $now);
        $offers->link((int)$promotion->get_id(), (int)$fr->get_id(), 2, null, $now);

        $user = $this->getDataGenerator()->create_user();
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$user->id,
            $ru->get_sku(),
            'M1-GLOBAL-PURCHASE',
            $now
        );

        $uuid = $this->cart_uuid('m1-global-fr-hold');
        $hold = CommercePedagogicalSeatReservationService::create($DB)->reserve(
            $fr->get_sku(),
            $uuid,
            0,
            1,
            $now,
            900
        );
        self::assertNotNull($hold);

        $capacity = CommercePedagogicalCapacityService::create($DB);
        foreach ([$ru->get_sku(), $fr->get_sku()] as $sku) {
            $snapshot = $capacity->for_product($sku, $now + 1);
            self::assertSame(1, $snapshot->get_promotion_occupied());
            self::assertSame(1, $snapshot->get_promotion_reserved_quantity());
            self::assertSame(0, $snapshot->get_promotion_remaining());
            self::assertFalse($snapshot->is_available());
            self::assertSame(
                CommercePedagogicalCapacityService::PROMOTION_FULL,
                $snapshot->get_blocking_reason()
            );
        }

        $promotion = $this->update_promotion(
            $promotion,
            $now + 2,
            3
        );

        foreach ([$ru->get_sku(), $fr->get_sku()] as $sku) {
            $snapshot = $capacity->for_product($sku, $now + 3);
            self::assertSame(1, $snapshot->get_promotion_remaining());
            self::assertTrue($snapshot->is_available());
            self::assertSame(1, $snapshot->get_remaining());
        }

        $stored = CommercePedagogicalSeatReservationRepository::create($DB)->find(
            (int)$promotion->get_id(),
            (int)$fr->get_id(),
            $uuid
        );
        self::assertSame($hold->get_id(), $stored?->get_id());
        self::assertSame($hold->get_expires_at(), $stored?->get_expires_at());
        self::assertTrue($stored?->is_active_at($now + 3));
    }

    public function test_live_offer_capacity_update_reopens_only_the_edited_offer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'm1-offer-live',
            $now,
            10
        );
        $ru = $this->product('M1-OFFER-RU');
        $fr = $this->product('M1-OFFER-FR');

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$promotion->get_id(), (int)$ru->get_id(), 1, null, $now);
        $offers->link((int)$promotion->get_id(), (int)$fr->get_id(), 2, null, $now);

        $user = $this->getDataGenerator()->create_user();
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$user->id,
            $ru->get_sku(),
            'M1-OFFER-PURCHASE',
            $now
        );

        $capacity = CommercePedagogicalCapacityService::create($DB);
        $before = $capacity->for_product($ru->get_sku(), $now + 1);
        self::assertSame(0, $before->get_offer_remaining());
        self::assertSame(
            CommercePedagogicalCapacityService::OFFER_FULL,
            $before->get_blocking_reason()
        );

        $frbefore = $capacity->for_product($fr->get_sku(), $now + 1);
        self::assertSame(2, $frbefore->get_offer_remaining());
        self::assertTrue($frbefore->is_available());

        // link() is intentionally an upsert: operationally increasing seats
        // must not create a second pedagogical offer row.
        $offers->link(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            3,
            null,
            $now + 2
        );

        $after = $capacity->for_product($ru->get_sku(), $now + 3);
        self::assertSame(3, $after->get_offer_capacity());
        self::assertSame(2, $after->get_offer_remaining());
        self::assertTrue($after->is_available());

        $frafter = $capacity->for_product($fr->get_sku(), $now + 3);
        self::assertSame(2, $frafter->get_offer_capacity());
        self::assertSame(2, $frafter->get_offer_remaining());
        self::assertCount(
            2,
            $offers->links_for_promotion((int)$promotion->get_id())
        );
    }

    public function test_lowering_capacity_does_not_evict_existing_participation_or_hold_and_can_be_reexpanded(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'm1-lower-live',
            $now,
            4
        );
        $product = $this->product('M1-LOWER');
        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$promotion->get_id(), (int)$product->get_id(), 4, null, $now);

        $user = $this->getDataGenerator()->create_user();
        $joins = CommercePedagogicalParticipationRepository::create($DB);
        $joins->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$user->id,
            $product->get_sku(),
            'M1-LOWER-PURCHASE',
            $now
        );

        $uuid = $this->cart_uuid('m1-lower-hold');
        $hold = CommercePedagogicalSeatReservationService::create($DB)->reserve(
            $product->get_sku(),
            $uuid,
            0,
            1,
            $now,
            900
        );
        self::assertNotNull($hold);

        $offers->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now + 1
        );
        $promotion = $this->update_promotion(
            $promotion,
            $now + 1,
            1
        );

        $capacity = CommercePedagogicalCapacityService::create($DB);
        $lowered = $capacity->for_product($product->get_sku(), $now + 2);
        self::assertSame(1, $lowered->get_promotion_occupied());
        self::assertSame(1, $lowered->get_promotion_reserved_quantity());
        self::assertSame(0, $lowered->get_remaining());
        self::assertFalse($lowered->is_available());
        self::assertSame(1, $joins->active_count_for_promotion((int)$promotion->get_id()));

        $stored = CommercePedagogicalSeatReservationRepository::create($DB)->find(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            $uuid
        );
        self::assertSame($hold->get_id(), $stored?->get_id());
        self::assertTrue($stored?->is_active_at($now + 2));

        $offers->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            3,
            null,
            $now + 3
        );
        $promotion = $this->update_promotion(
            $promotion,
            $now + 3,
            3
        );

        $reexpanded = $capacity->for_product($product->get_sku(), $now + 4);
        self::assertSame(1, $reexpanded->get_remaining());
        self::assertTrue($reexpanded->is_available());
    }

    public function test_sales_may_close_before_pedagogical_start_and_extension_reopens_sales_immediately(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $startsat = $now + DAYSECS;
        $promotion = $this->promotion(
            (int)$course->id,
            'm1-window-before-start',
            $now,
            10,
            CommercePedagogicalPromotionStatus::SCHEDULED,
            $now - HOURSECS,
            $now - MINSECS,
            $startsat
        );
        $product = $this->product('M1-WINDOW');
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            10,
            null,
            $now
        );

        $capacity = CommercePedagogicalCapacityService::create($DB);
        $closed = $capacity->for_product($product->get_sku(), $now);
        self::assertFalse($closed->are_sales_open());
        self::assertFalse($closed->is_available());
        self::assertSame(
            CommercePedagogicalCapacityService::SALES_CLOSED,
            $closed->get_blocking_reason()
        );
        self::assertSame($startsat, $promotion->get_starts_at());

        $promotion = $this->update_promotion(
            $promotion,
            $now + 1,
            10,
            CommercePedagogicalPromotionStatus::SCHEDULED,
            $promotion->get_sales_opens_at(),
            $now + HOURSECS,
            $startsat
        );

        $reopened = $capacity->for_product($product->get_sku(), $now + 2);
        self::assertTrue($reopened->are_sales_open());
        self::assertTrue($reopened->is_available());
        self::assertSame(10, $reopened->get_remaining());
        self::assertSame($startsat, $promotion->get_starts_at());
    }
}
