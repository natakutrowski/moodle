<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use block_xp\local\ruletype\limit_spec;
use local_subscriptions\commerce\education\xp\CommercePedagogicalActivityXpGate;
use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupReplayBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalLevelupXpBridgeService;
use local_subscriptions\commerce\education\xp\CommercePedagogicalXpRepository;

/** @covers \local_subscriptions\commerce\education\xp\CommercePedagogicalActivityXpGate */
final class commerce_797m55_qualified_activity_xp_test extends \advanced_testcase {
    /** @return array{int,int,int,int,int} promo, course, user, cmid, module context. */
    private function create_fixture(int $xpstart): array {
        global $DB;
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $module = $this->getDataGenerator()->create_module('page', [
            'course' => (int)$course->id, 'section' => 1,
        ]);
        $cm = get_coursemodule_from_instance('page', (int)$module->id, (int)$course->id, false, MUST_EXIST);
        $ctxid = (int)\context_module::instance((int)$cm->id)->id;

        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'qualified-' . bin2hex(random_bytes(4)),
            'name' => 'Qualified XP test',
            'courseid' => (int)$course->id,
            'status' => 'started',
            'published' => 1,
            'salesopensat' => null,
            'salesclosesat' => null,
            'startsat' => $now - 100,
            'xpstartsat' => $xpstart,
            'leaderboardhidehours' => 48,
            'endsat' => null,
            'capacitytotal' => 10,
            'createdby' => 2,
            'modifiedby' => 2,
            'timecreated' => $now - 100,
            'timemodified' => $now,
        ]);
        $productid = (int)$DB->insert_record('local_subs_commerce_product', (object)[
            'sku' => 'QXP-' . bin2hex(random_bytes(5)),
            'type' => 'course_access', 'status' => 'active',
            'name' => 'Qualified XP product', 'description' => '', 'metadatajson' => '{}',
            'availablefrom' => null, 'availableuntil' => null,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_join', (object)[
            'promotionid' => $promotionid, 'courseid' => (int)$course->id,
            'productid' => $productid, 'userid' => (int)$user->id,
            'purchasereference' => 'qualified-' . bin2hex(random_bytes(5)),
            'state' => 'active', 'timecreated' => $now - 100,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'userid' => (int)$user->id, 'courseid' => (int)$course->id,
            'promotionid' => $promotionid, 'profile' => 'promotion_progressive',
            'timecreated' => $now - 100, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promotionid, 'itemtype' => 'course_section',
            'itemid' => (int)$cm->section, 'position' => 1,
            'unlocksat' => $now - 20, 'createdby' => 2, 'modifiedby' => 2,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        return [$promotionid, (int)$course->id, (int)$user->id, (int)$cm->id, $ctxid];
    }

    public function test_configured_promotion_rejects_unattributed_gain_and_requires_open_section(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$pid, $courseid, $userid, $cmid, $ctxid] = $this->create_fixture($now - 10);
        $bridge = CommercePedagogicalLevelupXpBridgeService::create($DB);
        $repo = CommercePedagogicalXpRepository::create($DB);
        // Generic world XP gives no context: no points in opted-in promotions.
        $this->assertSame([], $bridge->record_global_gain($userid, 10, $now, 'no-source'));
        $this->assertNull($bridge->record_gain($userid, $courseid, 10, $now, 'no-source-course'));
        $this->assertSame([], $bridge->record_qualified_activity_gain(
            $userid, 10, $now - 11, $ctxid, 21, 'activity_viewed', null
        ));
        // Explicitly closed until tomorrow.
        $DB->set_field('local_subs_commerce_ped_cal', 'unlocksat', $now + 3600,
            ['promotionid' => $pid, 'itemtype' => 'course_section']);
        $this->assertSame([], $bridge->record_qualified_activity_gain(
            $userid, 10, $now, $ctxid, 21, 'activity_viewed', null
        ));
        $DB->set_field('local_subs_commerce_ped_cal', 'unlocksat', $now - 20,
            ['promotionid' => $pid, 'itemtype' => 'course_section']);
        // System context has no unique activity -> fail closed.
        $this->assertSame([], $bridge->record_qualified_activity_gain(
            $userid, 10, $now, (int)\context_system::instance()->id, 21, 'activity_viewed', null
        ));
        $awards = $bridge->record_qualified_activity_gain($userid, 10, $now, $ctxid, 21, 'activity_viewed', null);
        $this->assertArrayHasKey($pid, $awards);
        $this->assertTrue($awards[$pid]->was_created());
        $again = $bridge->record_qualified_activity_gain($userid, 10, $now + 1, $ctxid, 21, 'activity_viewed', null);
        $this->assertFalse($again[$pid]->was_created());
        $this->assertSame(10, $repo->user_points($pid, $userid));
    }

    /**
     * Moodle 5 stores H5P modules inside delegated mod_subsection sections.
     * The promotion calendar has an entry only for the major parent section.
     */
    public function test_delegated_subsection_inherits_major_section_xp_unlock(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$pid, $courseid, $userid, $cmid, $ctxid] = $this->create_fixture($now - 30);

        $parentid = (int)$DB->get_field('course_modules', 'section', ['id' => $cmid], MUST_EXIST);
        $this->getDataGenerator()->create_module('subsection', [
            'course' => $courseid,
            'section' => 1,
            'name' => 'Grammar subsection',
        ]);
        $children = $DB->get_records('course_sections', [
            'course' => $courseid,
            'component' => 'mod_subsection',
        ]);
        $this->assertCount(1, $children);
        $child = reset($children);
        $childid = (int)$child->id;
        $this->assertNotSame($parentid, $childid);

        $hierarchy = \local_subscriptions\commerce\education\calendar\CommercePedagogicalSectionHierarchy::create($DB);
        $this->assertSame($parentid, $hierarchy->subsection_parents($courseid)[$childid]);
        // Move the test page into the delegated section. Gate reads its cm
        // record directly, just as it does for a real H5P in that section.
        $DB->set_field('course_modules', 'section', $childid, ['id' => $cmid]);

        $gate = new CommercePedagogicalActivityXpGate($DB);
        $this->assertSame($cmid, $gate->eligible_cmid($pid, $courseid, $ctxid, $now));
        $DB->set_field('local_subs_commerce_ped_cal', 'unlocksat', $now + 3600, [
            'promotionid' => $pid,
            'itemtype' => 'course_section',
            'itemid' => $parentid,
        ]);
        $this->assertNull($gate->eligible_cmid($pid, $courseid, $ctxid, $now));
        $this->assertSame($cmid, $gate->eligible_cmid($pid, $courseid, $ctxid, $now + 3600));
    }

    public function test_levelup_bridge_callback_delivers_real_activity_context(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/blocks/campusfrxpbridge/lib.php');
        $this->assertTrue(function_exists('block_campusfrxpbridge_xp_points_awarded_with_source'));
        $now = time();
        [$pid, $courseid, $userid, $cmid, $ctxid] = $this->create_fixture($now - 30);
        $reason = new \block_xp\local\reason\activity_viewed_reason();
        $reason->set_env_id($ctxid);
        // The bridge must use the true module context even though personal
        // Level Up XP may run in sitewide system-world mode.
        \block_campusfrxpbridge_xp_points_awarded_with_source(
            \context_module::instance($cmid), $userid, 9, 31, $reason
        );
        $this->assertSame(9, CommercePedagogicalXpRepository::create($DB)->user_points($pid, $userid));
        \block_campusfrxpbridge_xp_points_awarded_with_source(
            \context_module::instance($cmid), $userid, 9, 31, $reason
        );
        $this->assertSame(9, CommercePedagogicalXpRepository::create($DB)->user_points($pid, $userid));

        // XP+ uses local_xp\local\reason\activity_completion_reason,
        // not block_xp\local\reason. The callback must use the same
        // normalised reason as persisted Level Up logs and cron reconciliation.
        $this->assertSame('activity_completion',
            CommercePedagogicalActivityXpGate::normalise_reason_name(
                \local_xp\local\reason\activity_completion_reason::class));
        $completionreason = \local_xp\local\reason\activity_completion_reason::from_context(
            \context_module::instance($cmid));
        \block_campusfrxpbridge_xp_points_awarded_with_source(
            \context_module::instance($cmid), $userid, 6, 32, $completionreason);
        $this->assertSame(15, CommercePedagogicalXpRepository::create($DB)->user_points($pid, $userid));
        $key = CommercePedagogicalActivityXpGate::source_key($cmid, 32, 'activity_completion', null);
        $hash = hash('sha256', implode("\n", ['block_xp', 'eligible_activity', $key]));
        $this->assertNotNull(CommercePedagogicalXpRepository::create($DB)
            ->find_by_source_hash($pid, $userid, $hash));
    }

    public function test_historical_personal_reward_replays_after_start_and_shares_dedup_key(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$pid, $courseid, $userid, $cmid, $ctxid] = $this->create_fixture($now - 10);
        $DB->insert_record('block_xp_logs', (object)[
            'contextid' => (int)\context_system::instance()->id,
            'userid' => $userid, 'points' => 12, 'reason' => 'activity_viewed',
            'subtype' => null, 'envid' => $ctxid, 'parentid' => null, 'objectid' => 45,
            'ruleid' => 22, 'reasontypehash' => substr(sha1('activity_viewed:'), 0, 9),
            'timerecorded' => $now - 50, 'legacysource' => null, 'legacyid' => null,
        ]);
        $replay = CommercePedagogicalLevelupReplayBridgeService::create($DB);
        $args = [$userid, 12, $now, (int)\context_system::instance()->id, 22,
            'activity_viewed', null, $ctxid, null, 45, limit_spec::SCOPE_ENV | limit_spec::SCOPE_OBJECT];
        $result = $replay->record_global_replay(...$args);
        $this->assertArrayHasKey($pid, $result);
        $this->assertTrue($result[$pid]->was_created());
        $again = $replay->record_global_replay(...$args);
        $this->assertFalse($again[$pid]->was_created());
        // A later normal gain for the SAME rule/activity is not a second reward.
        $gain = CommercePedagogicalLevelupXpBridgeService::create($DB)
            ->record_qualified_activity_gain($userid, 12, $now + 1, $ctxid, 22, 'activity_viewed', null);
        $this->assertFalse($gain[$pid]->was_created());
        $this->assertSame(12, CommercePedagogicalXpRepository::create($DB)->user_points($pid, $userid));
    }
}
