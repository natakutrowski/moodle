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
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantMoveException;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantService;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797m25_participant_group_move_guard_test extends advanced_testcase {
    private function promotion(int $courseid): CommercePedagogicalPromotion {
        global $DB;
        $now = time();

        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'm25-participants',
                'M2.5 Participants',
                $courseid,
                CommercePedagogicalPromotionStatus::STARTED,
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

    private function participant(int $courseid, int $userid, int $promotionid): void {
        global $DB;
        $now = time();

        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null,
                $courseid,
                $userid,
                $promotionid,
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                null,
                null,
                $now,
                $now
            )
        );
    }

    public function test_full_target_group_is_business_rejection_and_keeps_source_membership(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $promotion = $this->promotion((int)$course->id);
        $now = time();

        $repo = CommercePedagogicalGroupRepository::create($DB);
        $repo->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            2,
            null,
            null,
            $now,
            $now
        ));

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $source = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'Source',
            0,
            null,
            null,
            null,
            null,
            null,
            $now
        );
        $target = $orchestrator->create_group(
            (int)$promotion->get_id(),
            'Target',
            1,
            null,
            null,
            null,
            null,
            null,
            $now
        );

        $moving = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$moving->id, (int)$course->id);
        $this->participant((int)$course->id, (int)$moving->id, (int)$promotion->get_id());
        $repo->save_membership((int)$source->get_id(), (int)$moving->id, null, $now);
        groups_add_member($source->get_moodle_group_id(), (int)$moving->id);

        for ($i = 0; $i < 2; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id);
            $this->participant((int)$course->id, (int)$user->id, (int)$promotion->get_id());
            $repo->save_membership((int)$target->get_id(), (int)$user->id, null, $now);
            groups_add_member($target->get_moodle_group_id(), (int)$user->id);
        }

        try {
            CommercePedagogicalParticipantService::create($DB)->move_group(
                (int)$promotion->get_id(),
                (int)$moving->id,
                (int)$target->get_id(),
                null,
                $now + 1
            );
            self::fail('Expected a full-group business rejection.');
        } catch (CommercePedagogicalParticipantMoveException $exception) {
            self::assertSame(
                CommercePedagogicalParticipantMoveException::GROUP_FULL,
                $exception->get_code_key()
            );
        }

        self::assertSame(
            $source->get_id(),
            $repo->group_for_user((int)$promotion->get_id(), (int)$moving->id)?->get_id()
        );
        self::assertTrue(groups_is_member($source->get_moodle_group_id(), (int)$moving->id));
        self::assertFalse(groups_is_member($target->get_moodle_group_id(), (int)$moving->id));
    }

    public function test_participants_admin_maps_full_group_rejection_to_warning(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/education/participants.php');

        self::assertStringContainsString(
            'catch (CommercePedagogicalParticipantMoveException $exception)',
            $source
        );
        self::assertStringContainsString(
            "get_string('commerce_education_participant_group_full', 'local_subscriptions')",
            $source
        );
        self::assertStringContainsString(
            '\\core\\output\\notification::NOTIFY_WARNING',
            $source
        );
    }
}
