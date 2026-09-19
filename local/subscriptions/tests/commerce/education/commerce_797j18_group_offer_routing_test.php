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
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalParticipationRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797j18_group_offer_routing_test extends advanced_testcase {
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

    private function promotion(int $courseid, int $now): CommercePedagogicalPromotion {
        global $DB;

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'j18-routing',
                'J18 routing',
                $courseid,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                null,
                null,
                $now,
                null,
                null,
                null,
                null,
                $now,
                $now
            )
        );
    }

    public function test_ru_purchase_fills_only_ru_groups_and_rolls_to_next_ru_group(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, $now);
        $ru = $this->product('J18-RU');
        $fr = $this->product('J18-FR');

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$promotion->get_id(), (int)$ru->get_id(), null, null, $now);
        $offers->link((int)$promotion->get_id(), (int)$fr->get_id(), null, null, $now);

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

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $ru1 = $orchestrator->create_group(
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
        $fr1 = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'FR 1',
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
        $ru2 = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'RU 2',
            2,
            null,
            'ru',
            null,
            null,
            null,
            $now,
            null,
            (int)$ru->get_id()
        );

        $users = [];
        for ($i = 0; $i < 3; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user(
                (int)$user->id,
                (int)$course->id
            );
            $users[] = $user;
        }

        self::assertSame(
            $ru1->get_id(),
            $orchestrator->assign_first_available_for_product(
                (int)$promotion->get_id(),
                (int)$ru->get_id(),
                (int)$users[0]->id,
                null,
                $now
            )?->get_id()
        );
        self::assertSame(
            $ru1->get_id(),
            $orchestrator->assign_first_available_for_product(
                (int)$promotion->get_id(),
                (int)$ru->get_id(),
                (int)$users[1]->id,
                null,
                $now
            )?->get_id()
        );
        self::assertSame(
            $ru2->get_id(),
            $orchestrator->assign_first_available_for_product(
                (int)$promotion->get_id(),
                (int)$ru->get_id(),
                (int)$users[2]->id,
                null,
                $now
            )?->get_id()
        );

        self::assertSame(2, $groups->member_count((int)$ru1->get_id()));
        self::assertSame(1, $groups->member_count((int)$ru2->get_id()));
        self::assertSame(0, $groups->member_count((int)$fr1->get_id()));
    }

    public function test_manual_move_to_other_offer_group_is_rejected(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, $now);
        $ru = $this->product('J18-MOVE-RU');
        $fr = $this->product('J18-MOVE-FR');

        $offers = CommercePedagogicalPromotionOfferRepository::create($DB);
        $offers->link((int)$promotion->get_id(), (int)$ru->get_id(), null, null, $now);
        $offers->link((int)$promotion->get_id(), (int)$fr->get_id(), null, null, $now);

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                6,
                null,
                null,
                $now,
                $now
            )
        );

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
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
        $frgroup = $orchestrator->create_group(
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

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);

        $DB->insert_record(
            'local_subs_commerce_ped_access',
            (object)[
                'courseid' => (int)$course->id,
                'userid' => (int)$user->id,
                'promotionid' => (int)$promotion->get_id(),
                'profile' => 'promotion_progressive',
                'createdby' => null,
                'modifiedby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        CommercePedagogicalParticipationRepository::create($DB)->record_active(
            (int)$promotion->get_id(),
            (int)$course->id,
            (int)$user->id,
            'J18-MOVE-RU',
            'J18-MOVE-PURCHASE',
            $now
        );

        $orchestrator->assign_first_available_for_product(
            (int)$promotion->get_id(),
            (int)$ru->get_id(),
            (int)$user->id,
            null,
            $now
        );

        self::assertSame(
            $rugroup->get_id(),
            $groups->group_for_user(
                (int)$promotion->get_id(),
                (int)$user->id
            )?->get_id()
        );

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage(
            'Target group belongs to another pedagogical offer.'
        );

        CommercePedagogicalParticipantService::create($DB)->move_group(
            (int)$promotion->get_id(),
            (int)$user->id,
            (int)$frgroup->get_id(),
            null,
            $now
        );
    }

    public function test_schema_has_product_binding_without_redundant_product_index(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString(
            'NAME="productid" TYPE="int" LENGTH="10" NOTNULL="false"',
            $install
        );
        self::assertStringContainsString(
            'NAME="product_fk" TYPE="foreign" FIELDS="productid"',
            $install
        );
        self::assertStringNotContainsString(
            'NAME="product_idx"',
            $install
        );
        self::assertStringContainsString(
            "upgrade_plugin_savepoint(\n            true,\n            2026091008",
            $upgrade
        );
    }
}
