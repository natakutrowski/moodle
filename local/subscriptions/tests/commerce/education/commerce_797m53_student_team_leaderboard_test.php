<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\xp\CommercePedagogicalStudentLeaderboardService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpAdjustmentService;

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

    public function test_competition_start_blocks_early_xp_and_hides_leaderboard_until_reveal(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Timed');
        $groupid = $this->create_group($promotionid, (int)$course->id, 'Les Etoiles', 0);
        $user = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $groupid);

        $start = time() + HOURSECS;
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $start, ['id' => $promotionid]);
        $DB->set_field('local_subs_commerce_ped_promo', 'leaderboardhidehours', 48, ['id' => $promotionid]);
        $xp = CommercePedagogicalXpService::create($DB);
        try {
            $xp->record_once($promotionid, (int)$user->id, 'block_xp', 'points_increased',
                'premature', 10, $start - 1, $start - 1);
            self::fail('No promotion XP should be recorded before scoring starts.');
        } catch (\coding_exception $e) {
            self::assertStringContainsString('scoring start', $e->getMessage());
        }
        self::assertSame(0, $DB->count_records('local_subs_commerce_ped_xp', ['promotionid' => $promotionid]));

        $xp->record_once($promotionid, (int)$user->id, 'block_xp', 'points_increased',
            'eligible', 10, $start, $start);
        $service = CommercePedagogicalStudentLeaderboardService::create($DB);
        $hidden = $service->get_for_user((int)$user->id, $start + HOURSECS);
        self::assertCount(1, $hidden);
        self::assertTrue($hidden[0]['isleaderboardhidden']);
        self::assertArrayNotHasKey('grouppoints', $hidden[0]);
        self::assertArrayNotHasKey('grouprank', $hidden[0]);
        self::assertArrayNotHasKey('leaderboard', $hidden[0]);
        self::assertNotEmpty($hidden[0]['revealdate']);
        self::assertFalse($hidden[0]['isxpnotstarted']);
        self::assertTrue($hidden[0]['isxpstarted']);
        $beforestart = $service->get_for_user((int)$user->id, $start - 1);
        self::assertTrue($beforestart[0]['isxpnotstarted']);
        self::assertNotEmpty($beforestart[0]['xpstartdate']);

        $revealed = $service->get_for_user((int)$user->id, $start + 48 * HOURSECS);
        self::assertCount(1, $revealed);
        self::assertSame(10, $revealed[0]['userpoints']);
        self::assertSame(10, $revealed[0]['grouppoints']);
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

    public function test_manual_user_and_group_bonuses_affect_only_their_promotion_scores(): void {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Bonuses');
        $g1 = $this->create_group($promotionid, (int)$course->id, 'Group one', 0);
        $g2 = $this->create_group($promotionid, (int)$course->id, 'Group two', 1);
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $g1);
        $this->attach_user($promotionid, (int)$course->id, (int)$other->id, $productid, $g2);
        $service = CommercePedagogicalXpAdjustmentService::create($DB);
        $now = time();
        $start = $now - HOURSECS;
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $start, ['id' => $promotionid]);
        $personalbefore = $DB->count_records('block_xp_logs', ['userid' => $user->id]);

        $userkey = bin2hex(random_bytes(16));
        $award = $service->award($promotionid, 'user', (int)$user->id, 20,
            'Participation exceptionnelle', (int)$admin->id, $userkey, $now);
        self::assertSame(20, CommercePedagogicalXpService::create($DB)->record_once(
            $promotionid, (int)$user->id, 'local_subscriptions', 'manual_adjustment',
            'adjustment:' . $award->id, 20, $now, $now)->get_contribution()->get_points());
        self::assertSame((int)$award->id, (int)$service->award($promotionid, 'user', (int)$user->id, 20,
            'Participation exceptionnelle', (int)$admin->id, $userkey, $now)->id);
        self::assertSame(1, $DB->count_records('local_subs_commerce_ped_xpadj',
            ['promotionid' => $promotionid, 'targettype' => 'user']));
        self::assertSame(20, (int)$DB->get_field_sql(
            'SELECT COALESCE(SUM(points), 0) FROM {local_subs_commerce_ped_xp} WHERE promotionid = :p AND userid = :u',
            ['p' => $promotionid, 'u' => $user->id]));
        self::assertSame($personalbefore, $DB->count_records('block_xp_logs', ['userid' => $user->id]));

        $service->award($promotionid, 'group', $g2, 50,
            'Equilibre du groupe incomplet', (int)$admin->id, bin2hex(random_bytes(16)), $now);
        self::assertSame(50, $service->group_bonus_totals($promotionid)[$g2]);
        $ranking = CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$user->id, $now);
        self::assertCount(1, $ranking);
        self::assertSame(20, $ranking[0]['userpoints']);
        self::assertSame(20, $ranking[0]['grouppoints']);
        self::assertSame(2, $ranking[0]['grouprank']);
        $otherchallenge = CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$other->id, $now);
        self::assertSame(0, $otherchallenge[0]['userpoints']);
        self::assertSame(50, $otherchallenge[0]['grouppoints']);
        self::assertSame(0, $DB->count_records('local_subs_commerce_ped_xp', ['promotionid' => $promotionid, 'userid' => $other->id]));
        self::assertSame($personalbefore, $DB->count_records('block_xp_logs', ['userid' => $user->id]));
        self::assertCount(1, $service->recent_for_target($promotionid, 'group', $g2));
    }

    public function test_manual_xp_uses_start_date_and_rejects_invalid_targets_and_points(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Bonus Validation');
        $groupid = $this->create_group($promotionid, (int)$course->id, 'Group', 0);
        $user = $this->getDataGenerator()->create_user();
        $outsider = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $groupid);
        $now = time();
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $now + 1000, ['id' => $promotionid]);
        $service = CommercePedagogicalXpAdjustmentService::create($DB);
        try {
            $service->award($promotionid, 'user', (int)$user->id, 5, 'Early bonus',
                (int)$admin->id, bin2hex(random_bytes(16)), $now);
            self::fail('Manual XP cannot be awarded before the start.');
        } catch (\coding_exception $e) {
            self::assertStringContainsString('scoring start', $e->getMessage());
        }
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $now - 1, ['id' => $promotionid]);
        foreach ([
            ['user', (int)$outsider->id, 10, 'Outside member'],
            ['user', (int)$user->id, 501, 'Too many points'],
            ['group', 99999999, 10, 'Wrong group id'],
            ['group', $groupid, 5001, 'Too many group points'],
            ['user', (int)$user->id, 20, 'short'],
        ] as [$target, $targetid, $points, $reason]) {
            try {
                $service->award($promotionid, $target, $targetid, $points, $reason,
                    (int)$admin->id, bin2hex(random_bytes(16)), $now);
                self::fail('Rejected request must not credit the promotion');
            } catch (\coding_exception $e) {
                self::assertNotEmpty($e->getMessage());
            }
        }
        self::assertSame(0, $DB->count_records('local_subs_commerce_ped_xpadj', ['promotionid' => $promotionid]));
        self::assertSame(0, $DB->count_records('local_subs_commerce_ped_xp', ['promotionid' => $promotionid]));
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

    public function test_cancelling_bonuses_preserves_audit_and_removes_only_promotion_scores(): void {
        global $DB;
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $productid = $this->create_product();
        $promotionid = $this->create_promotion((int)$course->id, 'M5.3 Cancellation');
        $groupid = $this->create_group($promotionid, (int)$course->id, 'Group one', 0);
        $user = $this->getDataGenerator()->create_user();
        $awarder = $this->getDataGenerator()->create_user();
        $canceller = $this->getDataGenerator()->create_user();
        $this->attach_user($promotionid, (int)$course->id, (int)$user->id, $productid, $groupid);
        $now = time();
        $DB->set_field('local_subs_commerce_ped_promo', 'xpstartsat', $now - HOURSECS,
            ['id' => $promotionid]);
        $service = CommercePedagogicalXpAdjustmentService::create($DB);
        $repository = \local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository::create($DB);
        $personalbefore = $DB->count_records('block_xp_logs', ['userid' => $user->id]);
        $normal = CommercePedagogicalXpService::create($DB)->record_once($promotionid, (int)$user->id,
            'block_xp', 'activity_completion', 'real-exercise', 7, $now, $now);
        $userbonus = $service->award($promotionid, 'user', (int)$user->id, 20,
            'Bonus test annulation user', (int)$awarder->id, bin2hex(random_bytes(16)), $now);
        $groupbonus = $service->award($promotionid, 'group', $groupid, 50,
            'Bonus test annulation group', (int)$awarder->id, bin2hex(random_bytes(16)), $now);
        self::assertSame(27, $repository->user_points($promotionid, (int)$user->id));
        self::assertSame(77, CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$user->id, $now)[0]['grouppoints']);

        $cancelled = $service->cancel($promotionid, 'user', (int)$user->id,
            (int)$userbonus->id, (int)$canceller->id, $now + 10);
        self::assertSame((int)$canceller->id, (int)$cancelled->cancelledby);
        self::assertSame($now + 10, (int)$cancelled->cancelledat);
        self::assertSame(7, $repository->user_points($promotionid, (int)$user->id));
        self::assertSame(57, CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$user->id, $now)[0]['grouppoints']);
        // Existing positive ledger row stays as auditable original evidence.
        self::assertSame(2, $DB->count_records('local_subs_commerce_ped_xp',
            ['promotionid' => $promotionid, 'userid' => $user->id]));
        $again = $service->cancel($promotionid, 'user', (int)$user->id,
            (int)$userbonus->id, (int)$awarder->id, $now + 99);
        self::assertSame((int)$canceller->id, (int)$again->cancelledby);
        self::assertSame($now + 10, (int)$again->cancelledat);
        try {
            $service->cancel($promotionid, 'group', $groupid, (int)$userbonus->id, (int)$awarder->id);
            self::fail('Cancellation with wrong target must be rejected.');
        } catch (\coding_exception $e) {
            self::assertStringContainsString('does not belong', $e->getMessage());
        }
        $service->cancel($promotionid, 'group', $groupid,
            (int)$groupbonus->id, (int)$canceller->id, $now + 20);
        self::assertSame([], $service->group_bonus_totals($promotionid));
        self::assertSame(7, CommercePedagogicalStudentLeaderboardService::create($DB)
            ->get_for_user((int)$user->id, $now)[0]['grouppoints']);
        self::assertSame(2, count($service->recent_for_target($promotionid, 'user', (int)$user->id)) +
            count($service->recent_for_target($promotionid, 'group', $groupid)));
        self::assertSame($personalbefore, $DB->count_records('block_xp_logs', ['userid' => $user->id]));
    }
}
