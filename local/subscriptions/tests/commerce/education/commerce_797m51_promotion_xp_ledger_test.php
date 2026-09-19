<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalGroupLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;

final class commerce_797m51_promotion_xp_ledger_test extends advanced_testcase {
    private function create_promotion(int $courseid, string $key): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => $key,
            'name' => $key,
            'courseid' => $courseid,
            'status' => 'open',
            'published' => 1,
            'salesopensat' => null,
            'salesclosesat' => null,
            'startsat' => null,
            'endsat' => null,
            'capacitytotal' => null,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_group(int $promotionid, int $courseid, string $name, int $position): int {
        global $DB;
        $moodlegroup = $this->getDataGenerator()->create_group([
            'courseid' => $courseid,
            'name' => $name . ' technical',
        ]);
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_ped_group', (object)[
            'promotionid' => $promotionid,
            'moodlegroupid' => $moodlegroup->id,
            'productid' => null,
            'displayname' => $name,
            'position' => $position,
            'tutorid' => null,
            'tutorname' => null,
            'supportlang' => null,
            'telegramref' => null,
            'levelupxp' => null,
            'active' => 1,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_product(): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'M51-' . strtoupper(random_string(12)),
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'M5.1 test product',
            'description' => null,
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function activate_participant(
        int $promotionid,
        int $courseid,
        int $userid,
        ?int $productid = null
    ): void {
        global $DB;
        $now = time();
        $productid ??= $this->create_product();
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid,
            'courseid' => $courseid,
            'userid' => $userid,
            'productid' => $productid,
            'purchasereference' => 'm51-' . $promotionid . '-' . $userid . '-' . random_string(8),
            'state' => 'active',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function set_membership(int $groupid, int $userid, bool $active): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_ped_gmem', (object)[
            'groupid' => $groupid,
            'userid' => $userid,
            'active' => $active ? 1 : 0,
            'createdby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_same_source_counts_once_per_user_and_promotion_but_again_in_another_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion1 = $this->create_promotion((int)$course->id, 'm51-p1-' . random_string(6));
        $promotion2 = $this->create_promotion((int)$course->id, 'm51-p2-' . random_string(6));
        $this->activate_participant($promotion1, (int)$course->id, (int)$user->id);
        $this->activate_participant($promotion2, (int)$course->id, (int)$user->id);

        $service = CommercePedagogicalXpService::create($DB);
        $first = $service->record_once($promotion1, (int)$user->id, 'block_gearup', 'mission', 'mission:42', 30, time(), time());
        $duplicate = $service->record_once($promotion1, (int)$user->id, 'block_gearup', 'mission', 'mission:42', 30, time(), time());
        $otherpromo = $service->record_once($promotion2, (int)$user->id, 'block_gearup', 'mission', 'mission:42', 30, time(), time());

        self::assertTrue($first->was_created());
        self::assertFalse($duplicate->was_created());
        self::assertSame($first->get_contribution()->get_id(), $duplicate->get_contribution()->get_id());
        self::assertTrue($otherpromo->was_created());
        self::assertSame(30, CommercePedagogicalXpRepository::create($DB)->user_points($promotion1, (int)$user->id));
        self::assertSame(30, CommercePedagogicalXpRepository::create($DB)->user_points($promotion2, (int)$user->id));
    }

    public function test_contribution_requires_active_promotion_participation(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->create_promotion((int)$course->id, 'm51-inactive-' . random_string(6));

        $this->expectException(\coding_exception::class);
        CommercePedagogicalXpService::create($DB)->record_once(
            $promotion,
            (int)$user->id,
            'block_gearup',
            'mission',
            'mission:99',
            10,
            time(),
            time()
        );
    }

    public function test_group_ranking_is_promotion_isolated_and_points_follow_current_membership(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $promotion = $this->create_promotion((int)$course->id, 'm51-rank-' . random_string(6));
        $otherpromotion = $this->create_promotion((int)$course->id, 'm51-other-' . random_string(6));
        $g1 = $this->create_group($promotion, (int)$course->id, 'Les Cigales', 0);
        $g2 = $this->create_group($promotion, (int)$course->id, 'Les Lavandes', 1);

        foreach ([$u1, $u2, $u3] as $user) {
            $this->activate_participant($promotion, (int)$course->id, (int)$user->id);
        }
        $this->activate_participant($otherpromotion, (int)$course->id, (int)$u1->id);

        $this->set_membership($g1, (int)$u1->id, true);
        $this->set_membership($g1, (int)$u2->id, true);
        $this->set_membership($g2, (int)$u3->id, true);

        $service = CommercePedagogicalXpService::create($DB);
        $now = time();
        $service->record_once($promotion, (int)$u1->id, 'block_gearup', 'mission', 'mission:1', 40, $now, $now);
        $service->record_once($promotion, (int)$u2->id, 'block_gearup', 'mission', 'mission:1', 10, $now, $now);
        $service->record_once($promotion, (int)$u3->id, 'block_gearup', 'mission', 'mission:1', 50, $now, $now);
        $service->record_once($otherpromotion, (int)$u1->id, 'block_gearup', 'mission', 'mission:1', 999, $now, $now);

        $ranking = CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotion);
        self::assertCount(2, $ranking);
        self::assertSame(50, $ranking[0]->get_points());
        self::assertSame(50, $ranking[1]->get_points());
        self::assertSame(1, $ranking[0]->get_rank());
        self::assertSame(1, $ranking[1]->get_rank());

        // Move u1: no XP row changes, but all of u1's promotion points follow the current membership.
        $DB->set_field('local_subs_commerce_ped_gmem', 'active', 0, ['groupid' => $g1, 'userid' => $u1->id]);
        $this->set_membership($g2, (int)$u1->id, true);

        $ranking = CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotion);
        self::assertSame($g2, $ranking[0]->get_group_id());
        self::assertSame(90, $ranking[0]->get_points());
        self::assertSame(1, $ranking[0]->get_rank());
        self::assertSame($g1, $ranking[1]->get_group_id());
        self::assertSame(10, $ranking[1]->get_points());
        self::assertSame(2, $ranking[1]->get_rank());
    }

    public function test_inactive_participant_contributions_do_not_count_in_group_score(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->create_promotion((int)$course->id, 'm51-inactive-score-' . random_string(6));
        $groupid = $this->create_group($promotion, (int)$course->id, 'Les Hirondelles', 0);
        $this->activate_participant($promotion, (int)$course->id, (int)$user->id);
        $this->set_membership($groupid, (int)$user->id, true);

        CommercePedagogicalXpService::create($DB)->record_once(
            $promotion,
            (int)$user->id,
            'block_gearup',
            'mission',
            'mission:7',
            25,
            time(),
            time()
        );

        $DB->set_field('local_subs_commerce_ped_join', 'state', 'cancelled', [
            'promotionid' => $promotion,
            'userid' => $user->id,
        ]);

        $ranking = CommercePedagogicalGroupLeaderboardService::create($DB)->get_ranking($promotion);
        self::assertCount(1, $ranking);
        self::assertSame(0, $ranking[0]->get_points());
        self::assertSame(0, $ranking[0]->get_member_count());
    }

    public function test_promotion_xp_does_not_write_personal_levelup_state(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $promotion = $this->create_promotion((int)$course->id, 'm51-personal-' . random_string(6));
        $this->activate_participant($promotion, (int)$course->id, (int)$user->id);

        $before = $DB->get_record('block_xp', ['courseid' => $course->id, 'userid' => $user->id], '*', IGNORE_MISSING);
        CommercePedagogicalXpService::create($DB)->record_once(
            $promotion,
            (int)$user->id,
            'block_gearup',
            'mission',
            'mission:11',
            15,
            time(),
            time()
        );
        $after = $DB->get_record('block_xp', ['courseid' => $course->id, 'userid' => $user->id], '*', IGNORE_MISSING);

        self::assertSame($before ? (int)$before->xp : null, $after ? (int)$after->xp : null);
    }
}
