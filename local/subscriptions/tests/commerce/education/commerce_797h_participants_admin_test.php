<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797h_participants_admin_test extends advanced_testcase {
    private function promotion(int $courseid): CommercePedagogicalPromotion {
        global $DB;
        $now = time();
        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'h-participants', 'H Participants', $courseid,
                CommercePedagogicalPromotionStatus::STARTED, true,
                null, null, $now, null, null, null, null, $now, $now
            )
        );
    }

    private function participant(int $courseid, int $userid, int $promotionid): void {
        global $DB;
        $now = time();
        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null, $courseid, $userid, $promotionid,
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                null, null, $now, $now
            )
        );
    }

    public function test_participant_view_lists_only_selected_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
        $this->participant((int)$course->id, (int)$user->id, (int)$promotion->get_id());

        $rows = CommercePedagogicalParticipantRepository::create($DB)
            ->for_promotion((int)$promotion->get_id());

        self::assertCount(1, $rows);
        self::assertSame((int)$user->id, (int)$rows[0]->userid);
        self::assertSame(CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE, $rows[0]->profile);
    }

    public function test_admin_can_promote_participant_to_lifetime_full(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id);
        $user = $this->getDataGenerator()->create_user();
        $this->participant((int)$course->id, (int)$user->id, (int)$promotion->get_id());

        $updated = CommercePedagogicalParticipantService::create($DB)->set_profile(
            (int)$promotion->get_id(),
            (int)$user->id,
            CommerceStudentAccessProfile::LIFETIME_FULL,
            null,
            time()
        );

        self::assertSame(CommerceStudentAccessProfile::LIFETIME_FULL, $updated->get_profile());
        self::assertSame((int)$promotion->get_id(), $updated->get_promotion_id());
    }

    public function test_admin_can_move_participant_between_groups(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
        $this->participant((int)$course->id, (int)$user->id, (int)$promotion->get_id());
        $now = time();

        $repo = CommercePedagogicalGroupRepository::create($DB);
        $repo->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(), true, 6, null, null, $now, $now
        ));
        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $g1 = $orchestrator->create_group((int)$promotion->get_id(), 'Groupe A', 0, null, null, null, null, null, $now);
        $g2 = $orchestrator->create_group((int)$promotion->get_id(), 'Groupe B', 1, null, null, null, null, null, $now);
        $orchestrator->assign_first_available((int)$promotion->get_id(), (int)$user->id, null, $now);

        CommercePedagogicalParticipantService::create($DB)->move_group(
            (int)$promotion->get_id(), (int)$user->id, (int)$g2->get_id(), null, $now + 1
        );

        self::assertFalse(groups_is_member($g1->get_moodle_group_id(), (int)$user->id));
        self::assertTrue(groups_is_member($g2->get_moodle_group_id(), (int)$user->id));
        self::assertSame(
            $g2->get_id(),
            $repo->group_for_user((int)$promotion->get_id(), (int)$user->id)?->get_id()
        );
    }

    public function test_manual_move_refuses_group_from_another_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $p1 = $this->promotion((int)$course->id);

        $now = time();
        $p2 = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'h-other', 'Other', (int)$course->id,
                CommercePedagogicalPromotionStatus::STARTED, true,
                null, null, $now, null, null, null, null, $now, $now
            )
        );
        $user = $this->getDataGenerator()->create_user();
        $this->participant((int)$course->id, (int)$user->id, (int)$p1->get_id());
        $foreign = CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$p2->get_id(), 'Foreign', 0, null, null, null, null, null, $now
        );

        $this->expectException(\coding_exception::class);
        CommercePedagogicalParticipantService::create($DB)->move_group(
            (int)$p1->get_id(), (int)$user->id, (int)$foreign->get_id(), null, $now
        );
    }
}
