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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalLifecycleService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationException;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k8_capacity_reservation_stress_test extends advanced_testcase {
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

    /**
     * @return array{
     *  courseid:int,
     *  promotion:CommercePedagogicalPromotion,
     *  ru:CommerceProduct,
     *  fr:CommerceProduct,
     *  groups:CommercePedagogicalGroupRepository
     * }
     */
    private function setup_dual_offer(
        int $now,
        int $globalcapacity,
        int $rucapacity,
        int $frcapacity,
        int $groupsize = 2,
        int $groupsperoffer = 2
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion =
            CommercePedagogicalPromotionRepository::create($DB)->save(
                new CommercePedagogicalPromotion(
                    null,
                    'k8-dual-' . substr(sha1((string)microtime(true)), 0, 8),
                    'K8 dual offer',
                    (int)$course->id,
                    CommercePedagogicalPromotionStatus::OPEN,
                    true,
                    null,
                    null,
                    $now,
                    null,
                    $globalcapacity,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $ru = $this->product('K8-RU-' . substr(sha1((string)random_int(1, PHP_INT_MAX)), 0, 8));
        $fr = $this->product('K8-FR-' . substr(sha1((string)random_int(1, PHP_INT_MAX)), 0, 8));

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            $rucapacity,
            null,
            $now
        );
        $offers->link(
            (int)$promotion->get_id(),
            (int)$fr->get_id(),
            $frcapacity,
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

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        for ($i = 1; $i <= $groupsperoffer; $i++) {
            $orchestrator->create_group(
                (int)$promotion->get_id(),
                'RU ' . $i,
                $i,
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
                'FR ' . $i,
                $i,
                null,
                'fr',
                null,
                null,
                null,
                $now,
                null,
                (int)$fr->get_id()
            );
        }

        return [
            'courseid' => (int)$course->id,
            'promotion' => $promotion,
            'ru' => $ru,
            'fr' => $fr,
            'groups' => $groups,
        ];
    }

    private function cart_uuid(string $seed): string {
        return substr(hash('sha256', $seed), 0, 32);
    }

    public function test_last_global_seat_cannot_be_reserved_twice_across_ru_and_fr(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 1, 1, 1, 1, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $this->cart_uuid('ru-last'),
            101,
            1,
            $now
        );

        try {
            $reservations->reserve(
                $setup['fr']->get_sku(),
                $this->cart_uuid('fr-last'),
                102,
                1,
                $now
            );
            self::fail('Expected the shared final promotion seat to be unavailable.');
        } catch (CommercePedagogicalSeatReservationException $e) {
            self::assertSame(
                CommercePedagogicalCapacityService::PROMOTION_FULL,
                $e->get_code_key()
            );
        }

        self::assertSame(
            0,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($setup['ru']->get_sku(), $now)
                ->get_promotion_remaining()
        );
        self::assertSame(
            0,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($setup['fr']->get_sku(), $now)
                ->get_promotion_remaining()
        );
    }

    public function test_offer_reservations_remain_independent_but_share_global_capacity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 4, 2, 2, 2, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $this->cart_uuid('ru-one'),
            201,
            1,
            $now
        );

        $ru = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now);
        $fr = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['fr']->get_sku(), $now);

        self::assertSame(1, $ru->get_offer_reserved_quantity());
        self::assertSame(0, $fr->get_offer_reserved_quantity());
        self::assertSame(1, $ru->get_promotion_reserved_quantity());
        self::assertSame(1, $fr->get_promotion_reserved_quantity());
        self::assertSame(1, $ru->get_offer_remaining());
        self::assertSame(2, $fr->get_offer_remaining());
        self::assertSame(3, $fr->get_promotion_remaining());
    }

    public function test_offer_capacity_can_fill_without_blocking_other_offer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 5, 1, 3, 3, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $this->cart_uuid('ru-offer-full'),
            301,
            1,
            $now
        );

        try {
            $reservations->reserve(
                $setup['ru']->get_sku(),
                $this->cart_uuid('ru-offer-rejected'),
                302,
                1,
                $now
            );
            self::fail('Expected RU offer capacity to be full.');
        } catch (CommercePedagogicalSeatReservationException $e) {
            self::assertSame(
                CommercePedagogicalCapacityService::OFFER_FULL,
                $e->get_code_key()
            );
        }

        $frhold = $reservations->reserve(
            $setup['fr']->get_sku(),
            $this->cart_uuid('fr-still-open'),
            303,
            1,
            $now
        );
        self::assertNotNull($frhold);
    }

    public function test_group_rollover_stays_inside_offer_and_uses_next_compatible_group(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 8, 4, 4, 1, 2);
        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $groups = $setup['groups'];
        $promotionid = (int)$setup['promotion']->get_id();

        $ruusers = [
            $this->getDataGenerator()->create_user(),
            $this->getDataGenerator()->create_user(),
        ];
        $fruser = $this->getDataGenerator()->create_user();

        $ru1 = $orchestrator->assign_first_available_for_product(
            $promotionid,
            (int)$setup['ru']->get_id(),
            (int)$ruusers[0]->id,
            null,
            $now
        );
        $ru2 = $orchestrator->assign_first_available_for_product(
            $promotionid,
            (int)$setup['ru']->get_id(),
            (int)$ruusers[1]->id,
            null,
            $now + 1
        );
        $fr1 = $orchestrator->assign_first_available_for_product(
            $promotionid,
            (int)$setup['fr']->get_id(),
            (int)$fruser->id,
            null,
            $now + 2
        );

        self::assertNotNull($ru1);
        self::assertNotNull($ru2);
        self::assertNotNull($fr1);
        self::assertNotSame($ru1->get_id(), $ru2->get_id());
        self::assertSame((int)$setup['ru']->get_id(), $ru1->get_product_id());
        self::assertSame((int)$setup['ru']->get_id(), $ru2->get_product_id());
        self::assertSame((int)$setup['fr']->get_id(), $fr1->get_product_id());

        self::assertSame(1, $groups->member_count((int)$ru1->get_id()));
        self::assertSame(1, $groups->member_count((int)$ru2->get_id()));
        self::assertSame(1, $groups->member_count((int)$fr1->get_id()));
    }

    public function test_group_capacity_blocks_only_matching_offer(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 10, 10, 10, 1, 1);
        $promotionid = (int)$setup['promotion']->get_id();
        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);

        $ruuser = $this->getDataGenerator()->create_user();
        $orchestrator->assign_first_available_for_product(
            $promotionid,
            (int)$setup['ru']->get_id(),
            (int)$ruuser->id,
            null,
            $now
        );

        $rusnapshot = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now + 1);
        $frsnapshot = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['fr']->get_sku(), $now + 1);

        self::assertSame(0, $rusnapshot->get_group_remaining());
        self::assertSame(
            CommercePedagogicalCapacityService::GROUP_FULL,
            $rusnapshot->get_blocking_reason()
        );
        self::assertSame(1, $frsnapshot->get_group_remaining());
        self::assertTrue($frsnapshot->is_available());
    }

    public function test_expired_hold_reopens_last_seat_without_cleanup(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 1, 1, 1, 1, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $this->cart_uuid('expires'),
            401,
            1,
            $now,
            5
        );

        self::assertSame(
            0,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($setup['fr']->get_sku(), $now + 4)
                ->get_remaining()
        );

        self::assertSame(
            1,
            CommercePedagogicalCapacityService::create($DB)
                ->for_product($setup['fr']->get_sku(), $now + 6)
                ->get_remaining()
        );

        $replacement = $reservations->reserve(
            $setup['fr']->get_sku(),
            $this->cart_uuid('replacement'),
            402,
            1,
            $now + 6
        );
        self::assertNotNull($replacement);
    }

    public function test_same_cart_renewal_does_not_double_count_itself(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 1, 1, 1, 1, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $uuid = $this->cart_uuid('same-cart');

        $first = $reservations->reserve(
            $setup['ru']->get_sku(),
            $uuid,
            501,
            1,
            $now,
            10
        );
        $second = $reservations->renew(
            $setup['ru']->get_sku(),
            $uuid,
            501,
            1,
            $now + 2,
            30
        );

        self::assertSame($first?->get_id(), $second?->get_id());
        self::assertSame($now + 32, $second?->get_expires_at());

        $snapshot = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now + 2);
        self::assertSame(1, $snapshot->get_reserved_quantity());
        self::assertSame(0, $snapshot->get_remaining());
    }

    public function test_consumed_hold_is_not_counted_as_active_reservation(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 2, 2, 2, 2, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $uuid = $this->cart_uuid('consume');

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $uuid,
            601,
            1,
            $now
        );
        self::assertTrue($reservations->consume(
            $setup['ru']->get_sku(),
            $uuid,
            'K8-PURCHASE-CONSUMED',
            $now + 1
        ));

        $snapshot = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now + 2);

        self::assertSame(0, $snapshot->get_reserved_quantity());

        $link = CommercePedagogicalPromotionOfferRepository::create($DB)
            ->sale_link_for_product($setup['ru']->get_sku());
        $reservation = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find(
                (int)$link['promotion']->get_id(),
                (int)$link['offer']->productid,
                $uuid
            );

        self::assertSame(
            CommercePedagogicalSeatReservation::CONSUMED,
            $reservation?->get_state()
        );
    }

    public function test_refund_releases_participation_and_group_seat_for_next_student(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 2, 2, 2, 1, 1);
        $promotionid = (int)$setup['promotion']->get_id();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $participations =
            CommercePedagogicalParticipationRepository::create($DB);
        $participations->record_active(
            $promotionid,
            $setup['courseid'],
            (int)$user1->id,
            $setup['ru']->get_sku(),
            'K8-REFUND-1',
            $now
        );

        $group = CommercePedagogicalGroupOrchestrator::create($DB)
            ->assign_first_available_for_product(
                $promotionid,
                (int)$setup['ru']->get_id(),
                (int)$user1->id,
                null,
                $now
            );
        self::assertNotNull($group);

        $before = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now);
        self::assertSame(0, $before->get_group_remaining());

        CommercePedagogicalLifecycleService::create($DB)
            ->terminate_purchase(
                'K8-REFUND-1',
                CommercePedagogicalParticipationRepository::REFUNDED,
                $now + 1
            );

        $after = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now + 1);
        self::assertSame(1, $after->get_group_remaining());
        self::assertTrue($after->is_available());

        $next = CommercePedagogicalGroupOrchestrator::create($DB)
            ->assign_first_available_for_product(
                $promotionid,
                (int)$setup['ru']->get_id(),
                (int)$user2->id,
                null,
                $now + 2
            );
        self::assertSame($group->get_id(), $next?->get_id());
    }

    public function test_release_cart_frees_all_active_holds_owned_by_cart(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $setup = $this->setup_dual_offer($now, 4, 2, 2, 2, 1);
        $reservations = CommercePedagogicalSeatReservationService::create($DB);
        $uuid = $this->cart_uuid('mixed-cart');

        $reservations->reserve(
            $setup['ru']->get_sku(),
            $uuid,
            701,
            1,
            $now
        );
        $reservations->reserve(
            $setup['fr']->get_sku(),
            $uuid,
            701,
            1,
            $now
        );

        self::assertSame(2, $reservations->release_cart($uuid, $now + 1));

        $ru = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['ru']->get_sku(), $now + 2);
        $fr = CommercePedagogicalCapacityService::create($DB)
            ->for_product($setup['fr']->get_sku(), $now + 2);

        self::assertSame(0, $ru->get_promotion_reserved_quantity());
        self::assertSame(0, $fr->get_promotion_reserved_quantity());
        self::assertSame(2, $ru->get_offer_remaining());
        self::assertSame(2, $fr->get_offer_remaining());
    }
}
