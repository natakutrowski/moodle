<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\access\CommerceStudentAccessProfile;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccess;
use local_subscriptions\commerce\education\access\CommerceStudentCourseAccessRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\participant\CommercePedagogicalParticipantRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797m74_groups_participants_ux_test extends advanced_testcase {
    private function promotion(int $courseid, string $key): CommercePedagogicalPromotion {
        global $DB;
        $now = time();
        return CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                $key,
                'M7.4 ' . $key,
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

    public function test_group_screen_is_operational_and_xp_is_read_only(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/education/groups.php');

        self::assertStringContainsString('CommercePedagogicalGroupLeaderboardService', $source);
        self::assertStringContainsString("'action', 'value' => 'backfill'", $source);
        self::assertStringContainsString('assign_first_available_for_product', $source);
        self::assertStringContainsString('commerce_education_m74_group_score', $source);
        self::assertStringContainsString('groups_for_promotion($promotionid)', $source);
        self::assertStringContainsString('get_position()', $source);

        // M5 contract: XP is derived, never a manual group field.
        self::assertStringNotContainsString("optional_param('levelupxp'", $source);
        self::assertStringNotContainsString("'name' => 'levelupxp'", $source);
    }

    public function test_participant_screen_exposes_search_offer_xp_and_guarded_moves(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/admin/commerce/education/participants.php');

        self::assertStringContainsString("optional_param('q', '', PARAM_TEXT)", $source);
        self::assertStringContainsString('CommercePedagogicalXpRepository', $source);
        self::assertStringContainsString('points_by_user($promotionid)', $source);
        self::assertStringContainsString('groupoccupancy', $source);
        self::assertStringContainsString('move_group(', $source);
        self::assertStringContainsString('CommercePedagogicalParticipantMoveException::GROUP_FULL', $source);
        self::assertStringContainsString('fullname($participant)', $source);
    }

    public function test_participant_repository_scopes_active_group_membership_to_current_promotion(): void {
        global $DB, $CFG;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/group/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $p1 = $this->promotion((int)$course->id, 'm74-a');
        $p2 = $this->promotion((int)$course->id, 'm74-b');
        $now = time();

        CommerceStudentCourseAccessRepository::create($DB)->save(
            new CommerceStudentCourseAccess(
                null,
                (int)$course->id,
                (int)$user->id,
                (int)$p1->get_id(),
                CommerceStudentAccessProfile::PROMOTION_PROGRESSIVE,
                null,
                null,
                $now,
                $now
            )
        );

        $orchestrator = CommercePedagogicalGroupOrchestrator::create($DB);
        $g1 = $orchestrator->create_group(
            (int)$p1->get_id(),
            'P1 Group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now
        );
        $g2 = $orchestrator->create_group(
            (int)$p2->get_id(),
            'P2 Group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_membership((int)$g1->get_id(), (int)$user->id, null, $now);
        $groups->save_membership((int)$g2->get_id(), (int)$user->id, null, $now);

        $rows = CommercePedagogicalParticipantRepository::create($DB)
            ->for_promotion((int)$p1->get_id());

        self::assertCount(1, $rows);
        self::assertSame((int)$g1->get_id(), (int)$rows[0]->pedagogicalgroupid);
        self::assertSame('P1 Group', (string)$rows[0]->groupname);
    }

    public function test_participant_repository_query_has_explicit_promotion_membership_scope(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/education/participant/CommercePedagogicalParticipantRepository.php'
        );

        self::assertStringContainsString('AND EXISTS (', $source);
        self::assertStringContainsString('gscope.promotionid = a.promotionid', $source);
        self::assertStringContainsString('gscope.active = 1', $source);
    }
}
