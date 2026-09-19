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
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalLifecycleService;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSaleException;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSalePolicy;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797i_lifecycle_edge_cases_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;
        return (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))->save(
            new CommerceProduct($sku, CommerceProductType::COURSE_ACCESS, CommerceProductStatus::ACTIVE, $sku)
        );
    }

    private function promotion(
        int $courseid,
        string $key,
        int $now,
        ?int $capacity = null,
        ?int $opens = null,
        ?int $closes = null
    ): CommercePedagogicalPromotion {
        global $DB;
        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, $key, $key, $courseid,
                CommercePedagogicalPromotionStatus::OPEN, true,
                $opens, $closes, $now, null, $capacity,
                null, null, $now, $now
            )
        );
    }

    public function test_sales_window_blocks_product_before_opening(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'future-sales', $now, null, $now + 3600, null);
        $product = $this->product('I-FUTURE');
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), null, null, $now
        );

        $this->expectException(CommercePedagogicalSaleException::class);
        CommercePedagogicalSalePolicy::create($DB)->assert_product_available('I-FUTURE', $now);
    }

    public function test_global_capacity_blocks_next_sale(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'global-full', $now, 1);
        $product = $this->product('I-GLOBAL');
        $user = $this->getDataGenerator()->create_user();

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), null, null, $now
        );
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(), (int)$course->id, (int)$user->id,
            'I-GLOBAL', 'PURCHASE-I-1', $now
        );

        try {
            CommercePedagogicalSalePolicy::create($DB)->assert_product_available('I-GLOBAL', $now);
            self::fail('Expected global capacity to block sale.');
        } catch (CommercePedagogicalSaleException $e) {
            self::assertSame('pedagogical_promotion_full', $e->get_code_key());
        }
    }

    public function test_per_offer_capacity_is_independent_inside_same_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'offer-full', $now, 10);
        $ru = $this->product('I-RU');
        $fr = $this->product('I-FR');
        $links = CommercePedagogicalPromotionOfferRepository::create($DB);
        $links->link((int)$promotion->get_id(), (int)$ru->get_id(), 1, null, $now);
        $links->link((int)$promotion->get_id(), (int)$fr->get_id(), 2, null, $now);

        $user = $this->getDataGenerator()->create_user();
        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(), (int)$course->id, (int)$user->id,
            'I-RU', 'PURCHASE-I-RU', $now
        );

        try {
            CommercePedagogicalSalePolicy::create($DB)->assert_product_available('I-RU', $now);
            self::fail('Expected RU offer capacity to block sale.');
        } catch (CommercePedagogicalSaleException $e) {
            self::assertSame('pedagogical_offer_full', $e->get_code_key());
        }

        CommercePedagogicalSalePolicy::create($DB)->assert_product_available('I-FR', $now);
    }

    public function test_refunded_participation_releases_capacity(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'refund-capacity', $now, 1);
        $product = $this->product('I-REFUND');
        $user = $this->getDataGenerator()->create_user();

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), null, null, $now
        );
        $joins = CommercePedagogicalParticipationRepository::create($DB);
        $joins->record_active(
            (int)$promotion->get_id(), (int)$course->id, (int)$user->id,
            'I-REFUND', 'PURCHASE-I-REFUND', $now
        );

        CommercePedagogicalLifecycleService::create($DB)->terminate_purchase(
            'PURCHASE-I-REFUND',
            CommercePedagogicalParticipationRepository::REFUNDED,
            $now + 1
        );

        self::assertSame(0, $joins->active_count_for_promotion((int)$promotion->get_id()));
        CommercePedagogicalSalePolicy::create($DB)->assert_product_available('I-REFUND', $now + 1);
    }

    public function test_schema_avoids_redundant_single_column_fk_indexes(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString('NAME="local_subs_commerce_ped_join"', $install);
        self::assertStringNotContainsString('NAME="promotion_idx" UNIQUE="false" FIELDS="promotionid"', $install);
        self::assertStringNotContainsString("add_index('product_idx'", $upgrade);
        self::assertStringContainsString("promotion_product_state_idx", $upgrade);
    }
}
