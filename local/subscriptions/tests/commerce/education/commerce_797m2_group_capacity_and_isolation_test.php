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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797m2_group_capacity_and_isolation_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;

        return (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));
    }

    private function promotion(
        int $courseid,
        string $key,
        int $capacity,
        int $now
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
                $now - HOURSECS,
                $now + DAYSECS,
                $now + DAYSECS,
                null,
                $capacity,
                null,
                null,
                $now,
                $now
            )
        );
    }

    private function participant(
        int $promotionid,
        int $courseid,
        int $productid,
        string $sku,
        string $reference,
        int $now
    ): int {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, $courseid);

        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            $promotionid,
            $courseid,
            (int)$user->id,
            $sku,
            $reference,
            $now
        );

        $assigned = CommercePedagogicalGroupOrchestrator::create($DB)
            ->assign_first_available_for_product(
                $promotionid,
                $productid,
                (int)$user->id,
                null,
                $now
            );

        self::assertNotNull($assigned);

        return (int)$user->id;
    }

    public function test_adding_group_during_sales_reopens_capacity_and_next_buyer_uses_new_group(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'm2-live-add', 4, $now);
        $fr = $this->product('M2-LIVE-FR');

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$fr->get_id(),
            4,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            2,
            null,
            null,
            $now,
            $now
        ));

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $first = $orchestrator->create_group(
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
            (int)$fr->get_id()
        );

        $this->participant(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$fr->get_id(),
            'M2-LIVE-FR',
            'M2-LIVE-P1',
            $now
        );
        $this->participant(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$fr->get_id(),
            'M2-LIVE-FR',
            'M2-LIVE-P2',
            $now
        );

        $full = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M2-LIVE-FR', $now);

        self::assertSame(2, $full->get_promotion_occupied());
        self::assertSame(2, $full->get_offer_occupied());
        self::assertSame(2, $full->get_group_occupied());
        self::assertSame(0, $full->get_group_remaining());
        self::assertSame(CommercePedagogicalCapacityService::GROUP_FULL, $full->get_blocking_reason());
        self::assertFalse($full->is_available());

        $second = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'Les Hirondelles',
            1,
            null,
            'fr',
            null,
            null,
            null,
            $now + 1,
            null,
            (int)$fr->get_id()
        );

        $reopened = CommercePedagogicalCapacityService::create($DB)
            ->for_product('M2-LIVE-FR', $now + 1);

        self::assertTrue($reopened->is_available());
        self::assertNull($reopened->get_blocking_reason());
        self::assertSame(2, $reopened->get_group_remaining());
        self::assertSame(2, $reopened->get_remaining());
        self::assertSame(2, $groups->member_count((int)$first->get_id()));
        self::assertSame(0, $groups->member_count((int)$second->get_id()));

        $thirduserid = $this->participant(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$fr->get_id(),
            'M2-LIVE-FR',
            'M2-LIVE-P3',
            $now + 2
        );

        self::assertSame(2, $groups->member_count((int)$first->get_id()));
        self::assertSame(1, $groups->member_count((int)$second->get_id()));
        self::assertSame(
            $second->get_id(),
            $groups->group_for_user(
                (int)$promotion->get_id(),
                $thirduserid
            )?->get_id()
        );
    }

    public function test_same_visible_group_name_is_isolated_between_promotions_on_same_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $september = $this->promotion((int)$course->id, 'm2-september', 6, $now);
        $november = $this->promotion((int)$course->id, 'm2-november', 6, $now);
        $sepfr = $this->product('M2-SEP-FR');
        $novfr = $this->product('M2-NOV-FR');

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$september->get_id(), (int)$sepfr->get_id(), 6, null, $now);
        $offers->link((int)$november->get_id(), (int)$novfr->get_id(), 6, null, $now);

        $groups = CommercePedagogicalGroupRepository::create($DB);
        foreach ([$september, $november] as $promotion) {
            $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                6,
                null,
                null,
                $now,
                $now
            ));
        }

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $sepcigales = $orchestrator->create_group(
            (int)$september->get_id(),
            'Les Cigales',
            0,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$sepfr->get_id()
        );
        $novcigales = $orchestrator->create_group(
            (int)$november->get_id(),
            'Les Cigales',
            0,
            null,
            'fr',
            null,
            null,
            null,
            $now,
            null,
            (int)$novfr->get_id()
        );

        self::assertSame('Les Cigales', $sepcigales->get_display_name());
        self::assertSame('Les Cigales', $novcigales->get_display_name());
        self::assertNotSame($sepcigales->get_id(), $novcigales->get_id());
        self::assertNotSame($sepcigales->get_moodle_group_id(), $novcigales->get_moodle_group_id());

        $sepmoodle = $DB->get_record('groups', ['id' => $sepcigales->get_moodle_group_id()], 'id,name', MUST_EXIST);
        $novmoodle = $DB->get_record('groups', ['id' => $novcigales->get_moodle_group_id()], 'id,name', MUST_EXIST);

        self::assertSame('Les Cigales · m2-september', $sepmoodle->name);
        self::assertSame('Les Cigales · m2-november', $novmoodle->name);

        $userid = $this->participant(
            (int)$september->get_id(),
            (int)$course->id,
            (int)$sepfr->get_id(),
            'M2-SEP-FR',
            'M2-SEP-P1',
            $now
        );

        self::assertSame(
            $sepcigales->get_id(),
            $groups->group_for_user((int)$september->get_id(), $userid)?->get_id()
        );
        self::assertNull(
            $groups->group_for_user((int)$november->get_id(), $userid)
        );
        self::assertSame(1, $groups->member_count((int)$sepcigales->get_id()));
        self::assertSame(0, $groups->member_count((int)$novcigales->get_id()));
    }

    public function test_increasing_group_size_during_sales_reopens_only_group_capacity(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'm2-resize', 5, $now);
        $ru = $this->product('M2-RESIZE-RU');

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            5,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            2,
            null,
            null,
            $now,
            $now
        ));

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $orchestrator->create_group(
            (int)$promotion->get_id(),
            'Les Lavandes',
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

        $this->participant((int)$promotion->get_id(), (int)$course->id, (int)$ru->get_id(), 'M2-RESIZE-RU', 'M2-R1', $now);
        $this->participant((int)$promotion->get_id(), (int)$course->id, (int)$ru->get_id(), 'M2-RESIZE-RU', 'M2-R2', $now);

        $before = CommercePedagogicalCapacityService::create($DB)->for_product('M2-RESIZE-RU', $now);
        self::assertSame(0, $before->get_group_remaining());
        self::assertSame(CommercePedagogicalCapacityService::GROUP_FULL, $before->get_blocking_reason());

        $current = $groups->get_configuration((int)$promotion->get_id());
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            3,
            $current->get_created_by(),
            null,
            $current->get_time_created(),
            $now + 1
        ));

        $after = CommercePedagogicalCapacityService::create($DB)->for_product('M2-RESIZE-RU', $now + 1);
        self::assertTrue($after->is_available());
        self::assertSame(1, $after->get_group_remaining());
        self::assertSame(3, $after->get_group_capacity());
        self::assertSame(3, $after->get_promotion_remaining());
        self::assertSame(3, $after->get_offer_remaining());
        self::assertSame(1, $after->get_remaining());
    }
}
