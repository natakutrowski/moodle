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
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\purchase\CommercePedagogicalPurchaseOrchestrator;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\course\CommerceCourseAccessFulfillmentHandler;

final class commerce_797m261_reused_product_promotion_routing_test extends advanced_testcase {
    private function promotion(
        int $courseid,
        string $key,
        string $status,
        bool $published,
        ?int $salesopen,
        ?int $salesclose,
        int $now
    ): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                $key,
                $courseid,
                $status,
                $published,
                $salesopen,
                $salesclose,
                $now + DAYSECS,
                null,
                10,
                null,
                null,
                $now,
                $now
            )
        );
    }

    public function test_same_product_routes_sale_and_fulfillment_to_current_cohort(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION,
            $now
        );

        $product = (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(new CommerceProduct(
            'M261-FR',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'M261 FR'
        ));

        $september = $this->promotion(
            (int)$course->id,
            'm261-september',
            CommercePedagogicalPromotionStatus::STARTED,
            true,
            $now - 30 * DAYSECS,
            $now - DAYSECS,
            $now
        );
        $november = $this->promotion(
            (int)$course->id,
            'm261-november',
            CommercePedagogicalPromotionStatus::SCHEDULED,
            true,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$september->get_id(), (int)$product->get_id(), 5, null, $now);
        $offers->link((int)$november->get_id(), (int)$product->get_id(), 5, null, $now);

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        foreach ([$september, $november] as $promotion) {
            $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                3,
                null,
                null,
                $now,
                $now
            ));
            $orchestrator->create_group(
                (int)$promotion->get_id(),
                'Les Cigales',
                0,
                null,
                'fr',
                null,
                null,
                null,
                $now,
                null,
                (int)$product->get_id()
            );
        }

        // Historical September participant must not consume November capacity.
        $olduser = $this->getDataGenerator()->create_user();
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$september->get_id(),
            (int)$course->id,
            (int)$olduser->id,
            'M261-FR',
            'M261-SEP-P1',
            $now
        );
        $orchestrator->assign_first_available_for_product(
            (int)$september->get_id(),
            (int)$product->get_id(),
            (int)$olduser->id,
            null,
            $now
        );

        $snapshot = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M261-FR', $now);
        self::assertSame((int)$november->get_id(), $snapshot->get_promotion_id());
        self::assertSame(0, $snapshot->get_promotion_occupied());
        self::assertSame(0, $snapshot->get_group_occupied());
        self::assertTrue($snapshot->is_available());

        $buyer = $this->getDataGenerator()->create_user();
        $cartuuid = str_repeat('d', 32);
        $reservation = CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M261-FR',
            $cartuuid,
            (int)$buyer->id,
            1,
            $now
        );
        self::assertSame((int)$november->get_id(), $reservation?->get_promotion_id());

        $grant = new CommerceEntitlementGrant(
            'grant-m261',
            'purchase-m261',
            'item-m261',
            'M261-FR',
            CommerceCourseAccessFulfillmentHandler::GRANT_TYPE,
            'course:' . (int)$course->id . ':full',
            1,
            (int)$buyer->id,
            'buyer@example.test',
            $now,
            null
        );

        CommercePedagogicalPurchaseOrchestrator::create($DB)->apply(
            [$grant],
            $now + 1,
            $cartuuid
        );

        self::assertTrue(
            CommercePedagogicalParticipationRepository::create($DB)->is_active(
                (int)$november->get_id(),
                (int)$buyer->id
            )
        );
        self::assertFalse(
            CommercePedagogicalParticipationRepository::create($DB)->is_active(
                (int)$september->get_id(),
                (int)$buyer->id
            )
        );

        $novgroup = $groups->group_for_user((int)$november->get_id(), (int)$buyer->id);
        self::assertNotNull($novgroup);
        self::assertSame('Les Cigales', $novgroup->get_display_name());
        self::assertNull($groups->group_for_user((int)$september->get_id(), (int)$buyer->id));

        $consumed = CommercePedagogicalSeatReservationRepository::create($DB)
            ->find_for_cart_product($cartuuid, (int)$product->get_id());
        self::assertSame((int)$november->get_id(), $consumed?->get_promotion_id());
        self::assertSame('consumed', $consumed?->get_state());
    }

    public function test_overlapping_open_windows_for_same_product_are_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $product = (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(new CommerceProduct(
            'M261-AMB',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'M261 ambiguous'
        ));

        $a = $this->promotion(
            (int)$course->id,
            'm261-a',
            CommercePedagogicalPromotionStatus::OPEN,
            true,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );
        $b = $this->promotion(
            (int)$course->id,
            'm261-b',
            CommercePedagogicalPromotionStatus::SCHEDULED,
            true,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$a->get_id(), (int)$product->get_id(), 3, null, $now);
        $offers->link((int)$b->get_id(), (int)$product->get_id(), 3, null, $now);

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage(
            'Several pedagogical promotions have sales open for the same Commerce product.'
        );
        CommercePedagogicalCapacityService::create($DB)
            ->for_product('M261-AMB', $now);
    }
}
