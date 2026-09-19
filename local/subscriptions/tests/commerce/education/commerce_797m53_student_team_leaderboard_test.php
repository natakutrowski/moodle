<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalStudentLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;

final class commerce_797m53_student_team_leaderboard_test extends advanced_testcase {
    private function create_product(): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'M53-' . strtoupper(random_string(10)),
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'M5.3 test product',
            'description' => null,
            'metadatajson' => null,
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    private function create_promotion(int $courseid, string $name, string $status = 'open'): int {
        global $DB;
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'm53-' . random_string(10),
            'name' => $name,
            'courseid' => $courseid,
            'status' => $status,
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
        $mg = $this->getDataGenerator()->create_group([
            'courseid' => $courseid,
            'name' => $name . ' technical',
        ]);
        $now = time();
        return (int)$DB->insert_record('local_subs_commerce_ped_group', (object)[
            'promotionid' => $promotionid,
            'moodlegroupid' => (int)$mg->id,
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

    private function attach_user(int $promotionid, int $courseid, int $userid, int $productid, int $groupid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid,
            'courseid' => $courseid,
            'userid' => $userid,
            'productid' => $productid,
            'purchasereference' => 'm53-' . $promotionid . '-' . $userid . '-' . random_string(6),
            'state' => 'active',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'promotionid' => $promotionid,
            'profile' => 'promotion_progressive',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_gmem', (object)[
            'groupid' => $groupid,
            'userid' => $userid,
            'active' => 1,
            'createdby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public function test_student_challenge_marks_current_group_and_uses_promotion_ledger(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Current');
        $g1 = $this->create_group($promotionid, (int)$course->id, 'Les Cigales', 0);
        $g2 = $this->create_group($promotionid, (int)$course->id, 'Les Hirondelles', 1);
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $g2);
        $this->attach_user($promotionid, (int)$course->id, (int)$other->id, $productid, $g1);

        $xp = CommercePedagogicalXpService::create($DB);
        $now = time();
        $xp->record_once($promotionid, (int)$user->id, 'block_xp', 'points_increased', 'gain:user', 7, $now, $now);
        $xp->record_once($promotionid, (int)$other->id, 'block_xp', 'points_increased', 'gain:other', 12, $now, $now);

        $challenges = CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$user->id, $now);

        self::assertCount(1, $challenges);
        $challenge = $challenges[0];
        self::assertSame($promotionid, $challenge['promotionid']);
        self::assertSame($g2, $challenge['groupid']);
        self::assertSame('Les Hirondelles', $challenge['groupname']);
        self::assertSame(7, $challenge['grouppoints']);
        self::assertSame(2, $challenge['grouprank']);
        self::assertSame(7, $challenge['userpoints']);

        $mine = array_values(array_filter(
            $challenge['leaderboard'],
            static fn(array $row): bool => (bool)$row['ismine']
        ));
        self::assertCount(1, $mine);
        self::assertSame($g2, $mine[0]['groupid']);
        self::assertSame(7, $mine[0]['points']);
    }

    public function test_archived_or_non_current_relation_is_not_exposed(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Archived', 'archived');
        $groupid = $this->create_group($promotionid, (int)$course->id, 'Les Lavandes', 0);
        $user = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $groupid);

        self::assertSame(
            [],
            CommercePedagogicalStudentLeaderboardService::create($DB)
                ->get_for_user((int)$user->id, time())
        );
    }

    public function test_mon_campus_template_contains_team_ranking_surface(): void {
        global $CFG;
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/customer/hub.mustache'
        );
        self::assertIsString($template);
        self::assertStringContainsString('commerce-customer-hub__team-challenges', $template);
        self::assertStringContainsString('{{#teamchallenges}}', $template);
        self::assertStringContainsString('{{#ismine}}', $template);
    }
}
