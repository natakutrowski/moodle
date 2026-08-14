<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerGamificationMergeService;

final class commerce_customer_gamification_merge_m16_test extends advanced_testcase {
    public function test_xp_totals_and_history_are_consolidated(): void {
        global $DB;
        $this->resetAfterTest();
        if (!$DB->get_manager()->table_exists(new \xmldb_table('block_xp'))) {
            $this->markTestSkipped('Level Up XP is not installed.');
        }
        $source = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $DB->insert_record('block_xp', (object)['courseid' => $course->id, 'userid' => $target->id, 'xp' => 100, 'lvl' => 1]);
        $DB->insert_record('block_xp', (object)['courseid' => $course->id, 'userid' => $source->id, 'xp' => 250, 'lvl' => 2]);

        $service = new CommerceCustomerGamificationMergeService($DB);
        $result = $service->merge((int)$source->id, (int)$target->id);

        $this->assertSame(1, $result['xp_totals']);
        $this->assertSame(350, (int)$DB->get_field('block_xp', 'xp', ['courseid' => $course->id, 'userid' => $target->id]));
        $this->assertFalse($DB->record_exists('block_xp', ['userid' => $source->id]));
    }

    public function test_quest_conflict_keeps_most_advanced_progress(): void {
        global $DB;
        $this->resetAfterTest();
        if (!$DB->get_manager()->table_exists(new \xmldb_table('block_gearup_mission_inst'))) {
            $this->markTestSkipped('Level Up Quest is not installed.');
        }
        $source = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();
        $context = \context_system::instance();
        $missionid = (int)$DB->insert_record('block_gearup_mission', (object)[
            'contextid' => $context->id, 'type' => 0, 'state' => 1, 'title' => 'M16',
            'description' => '', 'instructions' => '', 'feedback' => '', 'repeatcount' => 0,
            'startmode' => 0, 'timelimit' => 0, 'visibility' => 1, 'usermodified' => 0,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $base = [
            'missionid' => $missionid, 'iteration' => 0, 'deadline' => 0, 'needsattention' => 0,
            'timestarted' => time() - 100, 'timecompleted' => 0, 'timeended' => 0,
            'usermodified' => 0, 'timecreated' => time() - 100, 'timemodified' => time(),
        ];
        $DB->insert_record('block_gearup_mission_inst', (object)array_merge($base, [
            'subjectid' => $target->id, 'state' => 1, 'counter' => 1, 'completionratio' => .20,
        ]));
        $DB->insert_record('block_gearup_mission_inst', (object)array_merge($base, [
            'subjectid' => $source->id, 'state' => 2, 'counter' => 4, 'completionratio' => 1.0,
            'timecompleted' => time(),
        ]));

        $service = new CommerceCustomerGamificationMergeService($DB);
        $result = $service->merge((int)$source->id, (int)$target->id);
        $merged = $DB->get_record('block_gearup_mission_inst', ['subjectid' => $target->id, 'missionid' => $missionid], '*', MUST_EXIST);

        $this->assertSame(1, $result['quest_conflicts_merged']);
        $this->assertSame(2, (int)$merged->state);
        $this->assertSame(4, (int)$merged->counter);
        $this->assertEquals(1.0, (float)$merged->completionratio);
        $this->assertFalse($DB->record_exists('block_gearup_mission_inst', ['subjectid' => $source->id]));
    }
}
