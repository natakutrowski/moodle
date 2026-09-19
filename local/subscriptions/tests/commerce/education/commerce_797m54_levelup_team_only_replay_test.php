<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use block_xp\local\ruletype\limit_spec;
use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupReplayBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;

/**
 * @covers \local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupReplayBridgeService
 */
final class commerce_797m54_levelup_team_only_replay_test extends \advanced_testcase {
    public function test_historical_personal_reward_can_contribute_once_per_new_promotion_without_personal_xp(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $user = $this->getDataGenerator()->create_user();
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $promotiona = $this->create_participation($user, $coursea, 'm54-a', $now - 50);
        $promotionb = $this->create_participation($user, $courseb, 'm54-b', $now - 20);

        // Simulate 100 personal Level Up XP already owned. M5.4 must never alter it.
        $DB->insert_record('block_xp', (object)[
            'courseid' => SITEID,
            'userid' => (int)$user->id,
            'xp' => 100,
        ]);

        // The matching Level Up reward was earned before either promotion join.
        $this->insert_xp_log(
            (int)$user->id,
            10,
            77,
            'activity_viewed',
            null,
            9001,
            null,
            42,
            $now - 100
        );

        $service = CommercePedagogicalLevelupReplayBridgeService::create($DB);
        $repo = CommercePedagogicalXpRepository::create($DB);
        $scope = limit_spec::SCOPE_ENV | limit_spec::SCOPE_OBJECT;

        $results = $service->record_global_replay(
            (int)$user->id,
            10,
            $now,
            (int)\context_system::instance()->id,
            77,
            'activity_viewed',
            null,
            9001,
            null,
            42,
            $scope
        );

        $this->assertArrayHasKey($promotiona, $results);
        $this->assertArrayHasKey($promotionb, $results);
        $this->assertTrue($results[$promotiona]->was_created());
        $this->assertTrue($results[$promotionb]->was_created());
        $this->assertSame(10, $repo->user_points($promotiona, (int)$user->id));
        $this->assertSame(10, $repo->user_points($promotionb, (int)$user->id));

        // Repeating the same already-rewarded activity cannot farm team points.
        $retry = $service->record_global_replay(
            (int)$user->id,
            10,
            $now + 1,
            (int)\context_system::instance()->id,
            77,
            'activity_viewed',
            null,
            9001,
            null,
            42,
            $scope
        );
        $this->assertFalse($retry[$promotiona]->was_created());
        $this->assertFalse($retry[$promotionb]->was_created());
        $this->assertSame(10, $repo->user_points($promotiona, (int)$user->id));
        $this->assertSame(10, $repo->user_points($promotionb, (int)$user->id));

        $this->assertSame(100, (int)$DB->get_field('block_xp', 'xp', [
            'courseid' => SITEID,
            'userid' => (int)$user->id,
        ], MUST_EXIST));
    }

    public function test_team_only_replay_is_rejected_when_same_source_was_rewarded_after_join(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $promotionid = $this->create_participation($user, $course, 'm54-postjoin', $now - 100);

        // Historical occurrence exists, but the same source was also rewarded after joining.
        $this->insert_xp_log((int)$user->id, 15, 88, 'quiz_attempt_finished', null, 9100, null, 55, $now - 200);
        $this->insert_xp_log((int)$user->id, 15, 88, 'quiz_attempt_finished', null, 9100, null, 55, $now - 20);

        $service = CommercePedagogicalLevelupReplayBridgeService::create($DB);
        $results = $service->record_global_replay(
            (int)$user->id,
            15,
            $now,
            (int)\context_system::instance()->id,
            88,
            'quiz_attempt_finished',
            null,
            9100,
            null,
            55,
            limit_spec::SCOPE_ENV | limit_spec::SCOPE_OBJECT
        );

        $this->assertSame([], $results);
        $this->assertSame(0, CommercePedagogicalXpRepository::create($DB)->user_points($promotionid, (int)$user->id));
    }

    public function test_team_only_replay_requires_a_matching_personal_reward_before_join(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $promotionid = $this->create_participation($user, $course, 'm54-nohistory', $now - 100);

        $results = CommercePedagogicalLevelupReplayBridgeService::create($DB)->record_global_replay(
            (int)$user->id,
            12,
            $now,
            (int)\context_system::instance()->id,
            99,
            'lesson_end_reached',
            null,
            9200,
            null,
            77,
            limit_spec::SCOPE_ENV | limit_spec::SCOPE_OBJECT
        );

        $this->assertSame([], $results);
        $this->assertSame(0, CommercePedagogicalXpRepository::create($DB)->user_points($promotionid, (int)$user->id));
    }

    private function create_participation(\stdClass $user, \stdClass $course, string $prefix, int $jointime): int {
        global $DB;
        $now = time();
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => $prefix . '-' . bin2hex(random_bytes(4)),
            'name' => 'M5.4 ' . $prefix,
            'courseid' => (int)$course->id,
            'status' => 'started',
            'published' => 1,
            'salesopensat' => $jointime - 3600,
            'salesclosesat' => $jointime + 3600,
            'startsat' => $jointime,
            'endsat' => null,
            'capacitytotal' => 10,
            'createdby' => 2,
            'modifiedby' => 2,
            'timecreated' => $jointime,
            'timemodified' => $now,
        ]);
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => strtoupper($prefix) . '-' . bin2hex(random_bytes(5)),
            'type' => 'course_access',
            'status' => 'active',
            'name' => 'M5.4 product',
            'description' => '',
            'metadatajson' => '{}',
            'availablefrom' => null,
            'availableuntil' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid,
            'courseid' => (int)$course->id,
            'productid' => $productid,
            'userid' => (int)$user->id,
            'purchasereference' => $prefix . '-ref-' . bin2hex(random_bytes(4)),
            'state' => 'active',
            'timecreated' => $jointime,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'userid' => (int)$user->id,
            'courseid' => (int)$course->id,
            'promotionid' => $promotionid,
            'profile' => 'promotion_progressive',
            'timecreated' => $jointime,
            'timemodified' => $now,
        ]);
        return $promotionid;
    }

    private function insert_xp_log(
        int $userid,
        int $points,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $timerecorded
    ): void {
        global $DB;
        $DB->insert_record('block_xp_logs', (object)[
            'contextid' => (int)\context_system::instance()->id,
            'userid' => $userid,
            'points' => $points,
            'reason' => $reason,
            'subtype' => $subtype,
            'envid' => $envid,
            'parentid' => $parentid,
            'objectid' => $objectid,
            'ruleid' => $ruleid,
            'reasontypehash' => substr(sha1($reason . ':' . ($subtype ?? '')), 0, 9),
            'timerecorded' => $timerecorded,
            'legacysource' => null,
            'legacyid' => null,
        ]);
    }
}
