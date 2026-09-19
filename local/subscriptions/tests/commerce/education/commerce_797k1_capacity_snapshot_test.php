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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSaleException;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSalePolicy;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797k1_capacity_snapshot_test extends advanced_testcase {
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
        ?int $capacity = null,
        ?int $opens = null
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
                $opens,
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

    public function test_unlinked_product_is_not_restricted(): void {
        global $DB;

        $this->resetAfterTest(true);
        $product = $this->product('K1-CLASSIC');

        $snapshot =
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K1-CLASSIC', time());

        self::assertFalse($snapshot->is_pedagogically_linked());
        self::assertTrue($snapshot->is_available());
        self::assertNull($snapshot->get_remaining());
        self::assertSame(0, $snapshot->get_reserved_quantity());

        CommercePedagogicalSalePolicy::create($DB)
            ->assert_product_available(
                $product->get_sku(),
                time()
            );
    }

    public function test_effective_remaining_is_minimum_of_promotion_offer_and_groups(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'k1-min',
            $now,
            10
        );
        $ru = $this->product('K1-RU');

        CommercePedagogicalPromotionOfferRepository::create($DB)
            ->link(
                (int)$promotion->get_id(),
                (int)$ru->get_id(),
                8,
                null,
                $now
            );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                3,
                null,
                null,
                $now,
                $now
            )
        );

        $grouporchestrator =
            CommercePedagogicalGroupOrchestrator::create($DB);
        $g1 = $grouporchestrator->create_group(
            (int)$promotion->get_id(),
            'RU 1',
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
        $g2 = $grouporchestrator->create_group(
            (int)$promotion->get_id(),
            'RU 2',
            1,
            null,
            'ru',
            null,
            null,
            null,
            $now,
            null,
            (int)$ru->get_id()
        );

        $joins =
            CommercePedagogicalParticipationRepository::create($DB);

        for ($i = 1; $i <= 5; $i++) {
            $user = $this->getDataGenerator()->create_user();

            $joins->record_active(
                (int)$promotion->get_id(),
                (int)$course->id,
                (int)$user->id,
                'K1-RU',
                'K1-RU-' . $i,
                $now
            );

            if ($i <= 3) {
                $groups->save_membership(
                    (int)$g1->get_id(),
                    (int)$user->id,
                    null,
                    $now
                );
            } else {
                $groups->save_membership(
                    (int)$g2->get_id(),
                    (int)$user->id,
                    null,
                    $now
                );
            }
        }

        $snapshot =
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K1-RU', $now);

        self::assertSame(10, $snapshot->get_promotion_capacity());
        self::assertSame(5, $snapshot->get_promotion_occupied());
        self::assertSame(5, $snapshot->get_promotion_remaining());

        self::assertSame(8, $snapshot->get_offer_capacity());
        self::assertSame(5, $snapshot->get_offer_occupied());
        self::assertSame(3, $snapshot->get_offer_remaining());

        self::assertTrue($snapshot->are_groups_enabled());
        self::assertSame(6, $snapshot->get_group_capacity());
        self::assertSame(5, $snapshot->get_group_occupied());
        self::assertSame(1, $snapshot->get_group_remaining());

        self::assertSame(1, $snapshot->get_remaining());
        self::assertTrue($snapshot->is_available());
        self::assertFalse($snapshot->is_sold_out());
    }

    public function test_ru_and_fr_group_capacity_are_independent(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'k1-languages',
            $now,
            20
        );
        $ru = $this->product('K1-LANG-RU');
        $fr = $this->product('K1-LANG-FR');

        $offers =
            CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            10,
            null,
            $now
        );
        $offers->link(
            (int)$promotion->get_id(),
            (int)$fr->get_id(),
            10,
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

        $rugroup = $orchestrator->create_group(
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

        for ($i = 1; $i <= 2; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $groups->save_membership(
                (int)$rugroup->get_id(),
                (int)$user->id,
                null,
                $now
            );
        }

        $capacity =
            CommercePedagogicalCapacityService::create($DB);

        $rusnapshot = $capacity->for_product(
            'K1-LANG-RU',
            $now
        );
        $frsnapshot = $capacity->for_product(
            'K1-LANG-FR',
            $now
        );

        self::assertSame(0, $rusnapshot->get_remaining());
        self::assertTrue($rusnapshot->is_sold_out());
        self::assertSame(
            CommercePedagogicalCapacityService::GROUP_FULL,
            $rusnapshot->get_blocking_reason()
        );

        self::assertSame(2, $frsnapshot->get_remaining());
        self::assertTrue($frsnapshot->is_available());
    }

    public function test_closed_sales_keep_capacity_information_but_block_sale(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'k1-future',
            $now,
            10,
            $now + HOURSECS
        );
        $product = $this->product('K1-FUTURE');

        CommercePedagogicalPromotionOfferRepository::create($DB)
            ->link(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                4,
                null,
                $now
            );

        $snapshot =
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K1-FUTURE', $now);

        self::assertFalse($snapshot->are_sales_open());
        self::assertFalse($snapshot->is_available());
        self::assertSame(4, $snapshot->get_remaining());
        self::assertSame(
            CommercePedagogicalCapacityService::SALES_CLOSED,
            $snapshot->get_blocking_reason()
        );

        try {
            CommercePedagogicalSalePolicy::create($DB)
                ->assert_product_available(
                    'K1-FUTURE',
                    $now
                );
            self::fail('Closed pedagogical sales must be rejected.');
        } catch (CommercePedagogicalSaleException $e) {
            self::assertSame(
                CommercePedagogicalCapacityService::SALES_CLOSED,
                $e->get_code_key()
            );
        }
    }

    public function test_offer_limit_wins_when_lower_than_other_limits(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion(
            (int)$course->id,
            'k1-offer-limit',
            $now,
            20
        );
        $product = $this->product('K1-OFFER');

        CommercePedagogicalPromotionOfferRepository::create($DB)
            ->link(
                (int)$promotion->get_id(),
                (int)$product->get_id(),
                2,
                null,
                $now
            );

        $snapshot =
            CommercePedagogicalCapacityService::create($DB)
                ->for_product('K1-OFFER', $now);

        self::assertSame(20, $snapshot->get_promotion_remaining());
        self::assertSame(2, $snapshot->get_offer_remaining());
        self::assertNull($snapshot->get_group_remaining());
        self::assertSame(2, $snapshot->get_remaining());
    }
}
