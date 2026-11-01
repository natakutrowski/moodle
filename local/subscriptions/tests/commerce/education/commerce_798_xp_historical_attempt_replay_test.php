<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommercePedagogicalAttemptReplayReconciler;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;

/** @covers \local_subscriptions\commerce\education\xp\CommercePedagogicalAttemptReplayReconciler */
final class commerce_798_xp_historical_attempt_replay_test extends \advanced_testcase {
    /**
     * Completed H5P reattempts after the scoring window earn team XP exactly
     * once; older attempts and lessons still locked earn nothing.
     */
    public function test_h5p_replay_requires_new_completed_attempt_and_open_major_section(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $start = $now - 900;
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => (int)$course->id, 'section' => 1,
        ]);
        $cm = get_coursemodule_from_instance('page', (int)$page->id, (int)$course->id, false, MUST_EXIST);
        $ctxid = (int)\context_module::instance((int)$cm->id)->id;
        // Use the real Moodle 5 H5P tables with a minimal fixture. No H5P
        // package is required because we are testing persisted attempts only.
        $instanceid = (int)$DB->insert_record('h5pactivity', (object)[
            'course' => (int)$course->id, 'name' => 'Replay fixture',
            'timecreated' => $start - 300, 'timemodified' => $start - 300,
        ]);
        $moduleid = (int)$DB->get_field('modules', 'id', ['name' => 'h5pactivity'], MUST_EXIST);
        $DB->set_field('course_modules', 'module', $moduleid, ['id' => $cm->id]);
        $DB->set_field('course_modules', 'instance', $instanceid, ['id' => $cm->id]);

        $promo = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'replay-' . bin2hex(random_bytes(5)),
            'name' => 'Historical attempt replay',
            'courseid' => (int)$course->id, 'status' => 'started', 'published' => 1,
            'startsat' => $start - 200, 'xpstartsat' => $start,
            'leaderboardhidehours' => 48,
            'capacitytotal' => 10, 'createdby' => 2, 'modifiedby' => 2,
            'timecreated' => $start - 300, 'timemodified' => $now,
        ]);
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'REP-' . bin2hex(random_bytes(5)),
            'type' => 'course_access', 'status' => 'active', 'name' => 'Replay test',
            'description' => '', 'metadatajson' => '{}',
            'timecreated' => $start - 300, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promo, 'courseid' => (int)$course->id,
            'productid' => $productid, 'userid' => (int)$user->id,
            'purchasereference' => 'test-' . bin2hex(random_bytes(4)),
            'state' => 'active', 'timecreated' => $start - 200,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'userid' => (int)$user->id, 'courseid' => (int)$course->id,
            'promotionid' => $promo, 'profile' => 'promotion_progressive',
            'timecreated' => $start - 200, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promo, 'itemtype' => 'course_section',
            'itemid' => (int)$cm->section, 'position' => 1,
            'unlocksat' => $now + 60,
            'createdby' => 2, 'modifiedby' => 2,
            'timecreated' => $start - 200, 'timemodified' => $now,
        ]);
        // Earlier personal Level Up completion, before the competition.
        $DB->insert_record('block_xp_logs', (object)[
            'contextid' => (int)\context_system::instance()->id,
            'userid' => (int)$user->id, 'points' => 12,
            'reason' => 'activity_completion', 'subtype' => null,
            'envid' => $ctxid, 'parentid' => null, 'objectid' => $instanceid,
            'ruleid' => 27, 'reasontypehash' => substr(sha1('activity_completion:'), 0, 9),
            'timerecorded' => $start - 210, 'legacysource' => null, 'legacyid' => null,
        ]);
        $this->insert_h5p_attempt($instanceid, (int)$user->id, 1, $start - 150, $start - 130);
        $reconciler = CommercePedagogicalAttemptReplayReconciler::create($DB);
        $repo = CommercePedagogicalXpRepository::create($DB);
        $this->assertSame(0, $repo->user_points($promo, (int)$user->id));
        $reconciler->run($now);
        $this->assertSame(0, $repo->user_points($promo, (int)$user->id));

        // A new completed attempt exists, but the lesson is still locked.
        $this->insert_h5p_attempt($instanceid, (int)$user->id, 2, $now - 40, $now - 30);
        $reconciler->run($now);
        $this->assertSame(0, $repo->user_points($promo, (int)$user->id));

        // The old locked attempt must not become eligible retroactively.
        $DB->set_field('local_subs_commerce_ped_cal', 'unlocksat', $now - 20, [
            'promotionid' => $promo, 'itemtype' => 'course_section',
        ]);
        // New attempt after unlock is eligible; the first two were too early.
        $this->insert_h5p_attempt($instanceid, (int)$user->id, 3, $now - 10, $now - 5);
        $a = $reconciler->run($now);
        $this->assertSame(1, $a['created']);
        $this->assertSame(12, $repo->user_points($promo, (int)$user->id));
        $b = $reconciler->run($now);
        $this->assertSame(0, $b['created']);
        $this->assertSame(1, $b['existing']);
        $this->assertSame(12, $repo->user_points($promo, (int)$user->id));

        // An early learner may earn PERSONAL XP from a future (locked)
        // lesson after XP scoring begins. Repeating the activity after its
        // scheduled release must still earn promotion XP exactly once.
        $DB->insert_record('block_xp_logs', (object)[
            'contextid' => (int)\context_system::instance()->id,
            'userid' => (int)$user->id, 'points' => 5,
            'reason' => 'activity_completion', 'subtype' => null,
            'envid' => $ctxid, 'parentid' => null, 'objectid' => $instanceid,
            'ruleid' => 28, 'reasontypehash' => substr(sha1('activity_completion:'), 0, 9),
            'timerecorded' => $now - 25, 'legacysource' => null, 'legacyid' => null,
        ]);
        $after = $reconciler->run($now);
        $this->assertSame(1, $after['created']);
        $this->assertSame(17, $repo->user_points($promo, (int)$user->id));
        $reconciler->run($now);
        $this->assertSame(17, $repo->user_points($promo, (int)$user->id));
    }

    /** Actual submission events, not page views, prove other module re-attempts. */
    public function test_other_modules_require_new_submission_event(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => (int)$course->id, 'section' => 1,
        ]);
        $cm = get_coursemodule_from_instance('page', (int)$module->id, (int)$course->id, false, MUST_EXIST);
        $ctxid = (int)\context_module::instance((int)$cm->id)->id;
        $reconciler = CommercePedagogicalAttemptReplayReconciler::create($DB);
        $this->assertNull($reconciler->completed_replay_time(
            'minilesson', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 20, $now
        ));
        $DB->insert_record('logstore_standard_log', (object)[
            'eventname' => '\\mod_minilesson\\event\\attempt_submitted',
            'component' => 'mod_minilesson', 'action' => 'submitted',
            'target' => 'attempt', 'crud' => 'c', 'edulevel' => 2,
            'contextid' => $ctxid, 'contextlevel' => CONTEXT_MODULE,
            'contextinstanceid' => (int)$cm->id, 'userid' => (int)$user->id,
            'courseid' => (int)$course->id, 'anonymous' => 0,
            'timecreated' => $now - 5, 'origin' => 'web', 'ip' => '127.0.0.1',
        ]);
        $this->assertSame($now - 5, $reconciler->completed_replay_time(
            'minilesson', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 20, $now
        ));
        $this->assertNull($reconciler->completed_replay_time(
            'minilesson', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 3, $now
        ));
        // A submission is recognised only for its own module type.
        $this->assertNull($reconciler->completed_replay_time(
            'readaloud', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 20, $now
        ));
        $this->assertNull($reconciler->completed_replay_time(
            'solo', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 20, $now
        ));
        $this->assertNull($reconciler->completed_replay_time(
            'unknown', 1, (int)$cm->id, (int)$user->id, (int)$course->id, $now - 20, $now
        ));
    }

    private function insert_h5p_attempt(int $instanceid, int $userid, int $attempt, int $created, int $modified): void {
        global $DB;
        $DB->insert_record('h5pactivity_attempts', (object)[
            'h5pactivityid' => $instanceid,
            'userid' => $userid,
            'attempt' => $attempt,
            'timecreated' => $created,
            'timemodified' => $modified,
            'completion' => 1,
        ]);
    }
}
