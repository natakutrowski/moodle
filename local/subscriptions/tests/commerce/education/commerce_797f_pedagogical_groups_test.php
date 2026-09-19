<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797f_pedagogical_groups_test extends advanced_testcase {
    private function promotion(int $courseid, string $key): CommercePedagogicalPromotion {
        global $DB;
        $now = time();
        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, $key, 'Promotion ' . $key, $courseid,
                CommercePedagogicalPromotionStatus::STARTED, true,
                null, null, $now, null, null, null, null, $now, $now
            )
        );
    }

    public function test_groups_are_optional_and_default_size_is_six(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'groups-default');

        $config = CommercePedagogicalGroupRepository::create($DB)
            ->get_configuration((int)$promotion->get_id());

        self::assertFalse($config->is_enabled());
        self::assertSame(6, $config->get_group_size());
    }

    public function test_same_visible_name_creates_distinct_moodle_groups_per_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $p1 = $this->promotion((int)$course->id, 'autumn-a1');
        $p2 = $this->promotion((int)$course->id, 'winter-a1');

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $g1 = $orchestrator->create_group((int)$p1->get_id(), 'Les Cigales', 0, null, 'fr', null, null, null, time());
        $g2 = $orchestrator->create_group((int)$p2->get_id(), 'Les Cigales', 0, null, 'fr', null, null, null, time());

        self::assertSame('Les Cigales', $g1->get_display_name());
        self::assertSame('Les Cigales', $g2->get_display_name());
        self::assertNotSame($g1->get_moodle_group_id(), $g2->get_moodle_group_id());

        $mg1 = $DB->get_record('groups', ['id' => $g1->get_moodle_group_id()], '*', MUST_EXIST);
        $mg2 = $DB->get_record('groups', ['id' => $g2->get_moodle_group_id()], '*', MUST_EXIST);
        self::assertNotSame($mg1->name, $mg2->name);
    }

    public function test_auto_assignment_fills_first_group_then_rolls_over(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'rollover');
        $repo = CommercePedagogicalGroupRepository::create($DB);
        $now = time();

        $repo->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(), true, 2, null, null, $now, $now
        ));

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $g1 = $orchestrator->create_group((int)$promotion->get_id(), 'Groupe 1', 0, null, null, null, null, null, $now);
        $g2 = $orchestrator->create_group((int)$promotion->get_id(), 'Groupe 2', 1, null, null, null, null, null, $now);

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        // Moodle group membership is meaningful for enrolled course participants.
        $this->getDataGenerator()->enrol_user((int)$u1->id, (int)$course->id);
        $this->getDataGenerator()->enrol_user((int)$u2->id, (int)$course->id);
        $this->getDataGenerator()->enrol_user((int)$u3->id, (int)$course->id);

        self::assertSame($g1->get_id(), $orchestrator->assign_first_available((int)$promotion->get_id(), (int)$u1->id, null, $now)->get_id());
        self::assertSame($g1->get_id(), $orchestrator->assign_first_available((int)$promotion->get_id(), (int)$u2->id, null, $now)->get_id());
        self::assertSame($g2->get_id(), $orchestrator->assign_first_available((int)$promotion->get_id(), (int)$u3->id, null, $now)->get_id());

        self::assertSame(2, $repo->member_count((int)$g1->get_id()));
        self::assertSame(1, $repo->member_count((int)$g2->get_id()));
        self::assertTrue(groups_is_member($g1->get_moodle_group_id(), (int)$u1->id));
        self::assertTrue(groups_is_member($g2->get_moodle_group_id(), (int)$u3->id));
    }

    public function test_disabled_groups_do_not_assign_student(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'disabled-groups');
        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $orchestrator->create_group((int)$promotion->get_id(), 'Groupe', 0, null, null, null, null, null, time());
        $user = $this->getDataGenerator()->create_user();

        self::assertNull($orchestrator->assign_first_available(
            (int)$promotion->get_id(), (int)$user->id, null, time()
        ));
    }

    public function test_group_metadata_supports_tutor_language_telegram_and_levelup(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id, 'metadata');
        $tutor = $this->getDataGenerator()->create_user();

        $group = CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(), 'Les Lavandes', 0, (int)$tutor->id,
            'ru', '@campusfr_group', 150, null, time()
        );

        self::assertSame((int)$tutor->id, $group->get_tutor_id());
        self::assertSame('ru', $group->get_support_language());
        self::assertSame('@campusfr_group', $group->get_telegram_reference());
        self::assertSame(150, $group->get_levelup_xp());
    }

    public function test_group_configuration_uses_unique_key_without_redundant_index(): void {
        $root = dirname(__DIR__, 3);
        $install = file_get_contents($root . '/db/install.xml');
        $upgrade = file_get_contents($root . '/db/upgrade.php');

        self::assertStringContainsString(
            '<KEY NAME="promotion_uq" TYPE="unique" FIELDS="promotionid" />',
            $install
        );
        self::assertStringNotContainsString(
            '<INDEX NAME="promotion_uix" UNIQUE="true" FIELDS="promotionid" />',
            $install
        );
        self::assertStringContainsString(
            "XMLDB_KEY_UNIQUE, ['promotionid']",
            $upgrade
        );
        self::assertStringNotContainsString(
            "add_index('promotion_uix'",
            $upgrade
        );
    }

}
