<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

namespace block_gearup;

use backup;
use block_gearup\local\assigner\type\group_members;
use block_gearup\local\mission\mission;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\objective\type\complete_activity;
use block_gearup\local\outcome\type\add_to_group;
use block_gearup\local\repository\mission_query;
use block_gearup\tests\base_testcase;
use context_course;
use restore_dbops;

/**
 * Test backup and retore.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_gearup_block_task
 * @covers     \backup_gearup_block_structure_step
 * @covers     \restore_gearup_block_task
 * @covers     \restore_gearup_block_structure_step
 */
final class backup_test extends base_testcase {

    /**
     * Test restore in new course.
     */
    public function test_restore_in_new_course(): void {
        global $DB;

        $data = $this->setup_courses();
        $c1 = $data['c1'];

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctxid = context_course::instance($newid)->id;
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE);

        // Very basic test.
        $this->assertEquals(3, $DB->count_records('block_gearup_mission', ['contextid' => $newctxid]));
        $this->assertEquals(8, $this->count_mission_instances($newctxid));
    }

    /**
     * Test restore in new course without users.
     */
    public function test_restore_in_new_course_without_users(): void {
        global $DB;

        $data = $this->setup_courses();
        $c1 = $data['c1'];

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctxid = context_course::instance($newid)->id;
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE, ['users' => false]);

        // Very basic test.
        $this->assertEquals(3, $DB->count_records('block_gearup_mission', ['contextid' => $newctxid]));
        $this->assertEquals(0, $this->count_mission_instances($newctxid));
    }

    /**
     * Test restore merge in other.
     */
    public function test_restore_merge_in_other(): void {
        global $DB;

        $data = $this->setup_courses();
        $c1 = $data['c1'];
        $c2 = $data['c2'];

        $this->setAdminUser();
        $backupid = $this->backup($c1);
        $this->restore($backupid, $c2->id, backup::TARGET_EXISTING_ADDING);
        $c2ctxid = context_course::instance($c2->id)->id;

        // Very basic test.
        $this->assertEquals(6, $DB->count_records('block_gearup_mission', ['contextid' => $c2ctxid]));
        $this->assertEquals(11, $this->count_mission_instances($c2ctxid));
    }

    /**
     * Test restore instance metadata.
     */
    public function test_restore_instance_metadata(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);

        $u1 = $dg->create_user();

        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');

        $que1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_QUEST,
            'startmode' => mission::START_OPTIN,
            'contextid' => $c1ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 8]],
        ]);
        $ach1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_ACHIEVEMENT,
            'contextid' => $c1ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $cha1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_CHALLENGE,
            'contextid' => $c1ctx->id,
            'repeatcount' => mission::REPEAT_ALWAYS,
            'timelimit' => DAYSECS,
            'objectives' => [['type' => 'manual', 'countneeded' => 16]],
        ]);

        $mique1 = $mo->assign_mission($que1a, $u1->id);
        $miach1 = $mo->assign_mission($ach1a, $u1->id);
        $micha1 = $mo->assign_mission($cha1a, $u1->id);

        // Start quest.
        $mo->start_instance($mique1);
        $obj = $mique1->get_objective_instances()[0];
        $obj->set_type_state((object) ['metadata' => 'test']); // Simlate type data.
        $oo->increment_instance_counter($obj, 6);
        $mo->evaluate_instance($mique1);

        // Increment one achievement.
        $obj = $miach1->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 2);
        $mo->evaluate_instance($miach1);

        // Iterate one challenge after fail at 50%.
        $obj = $micha1->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 8);
        $mo->evaluate_instance($micha1);
        $mo->end_instance($micha1);

        // Progress on one challenge.
        $micha2 = $mr->get_instances($cha1a->get_id(), 0, 1, [['iteration', SORT_DESC]], null, $u1->id)[0];
        $obj = $micha2->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 12);
        $mo->evaluate_instance($micha2);

        $this->setAdminUser();
        $this->add_block_to_course($c1->id);
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctxid = context_course::instance($newid)->id;
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE);

        // Very basic test.
        $this->assertEquals(3, $DB->count_records('block_gearup_mission', ['contextid' => $newctxid]));
        $this->assertEquals(4, $this->count_mission_instances($newctxid));

        // Validate that all the data is the same.
        $insts = [$mique1->get_id(), $miach1->get_id(), $micha1->get_id(), $micha2->get_id()];
        foreach ($insts as $instid) {
            $mibefore = $DB->get_record('block_gearup_mission_inst', ['id' => $instid], '*', MUST_EXIST);
            $mbefore = $DB->get_record('block_gearup_mission', ['id' => $mibefore->missionid], '*', MUST_EXIST);
            $miafter = $this->get_mission_instance_by_title($newctxid, $mibefore->iteration, $mbefore->title);

            $props = array_keys(array_diff_key((array) $mibefore, ['id' => 1, 'missionid' => 1, 'usermodified' => 1]));
            foreach ($props as $prop) {
                $this->assertEquals($mibefore->{$prop}, $miafter->{$prop}, $prop);
            }

            $objsbefore = array_values($DB->get_records('block_gearup_objective_inst',
                ['missioninstid' => $mibefore->id],
                'id',
                '*'
            ));
            $objsafter = array_values($DB->get_records('block_gearup_objective_inst',
                ['missioninstid' => $miafter->id],
                'id',
                '*'
            ));
            $this->assertEquals(count($objsbefore), count($objsafter));
            foreach ($objsbefore as $i => $objbefore) {
                $objafter = $objsafter[$i];
                $props = array_keys(array_diff_key((array) $objbefore, ['id' => 1, 'missioninstid' => 1, 'objectiveid' => 1,
                    'usermodified' => 1]));
                foreach ($props as $prop) {
                    $this->assertEquals($objbefore->{$prop}, $objafter->{$prop}, $prop);
                }
            }
        }
    }

    public function test_restore_processor(): void {
        global $DB;

        $mr = di::get('repository');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);
        $g1 = $dg->create_group(['courseid' => $c1->id]);
        $g2 = $dg->create_group(['courseid' => $c1->id]);
        $page = $dg->create_module('page', ['course' => $c1]);
        $u1 = $dg->create_user();
        $dg->enrol_user($u1->id, $c1->id);

        $this->add_block_to_course($c1->id);
        $mission = $gudg->create_quest([
            'contextid' => $c1ctx->id,
            'assigners' => [[
                'type' => 'group_members',
                'configdata' => ['groupids' => [$g1->id]],
            ]],
            'objectives' => [[
                'type' => 'complete_activity',
                'configdata' => ['cmid' => $page->cmid, 'which' => complete_activity::WHICH_SPECIFIC_IN_COURSE],
            ]],
            'outcomes' => [[
                'type' => 'add_to_group',
                'configdata' => ['groupid' => $g2->id],
            ]],
        ]);

        $missions = iterator_to_array($mr->get_missions_from_query((new mission_query($c1ctx))->set_context_id($c1ctx->id)));
        $this->assertCount(1, $missions);

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctx = context_course::instance($newid);
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE, ['users' => false]);

        $newg1 = $DB->get_record('groups', ['courseid' => $newid, 'name' => $g1->name], '*', MUST_EXIST);
        $newg2 = $DB->get_record('groups', ['courseid' => $newid, 'name' => $g2->name], '*', MUST_EXIST);
        $newpage = array_values(get_fast_modinfo($newid)->get_cms())[0];

        $missions = iterator_to_array($mr->get_missions_from_query((new mission_query($newctx))->set_context_id($newctx->id)));
        $this->assertCount(1, $missions);

        $newmission = $missions[0]->mission;
        $this->assertNotEquals($mission->get_id(), $newmission->get_id());

        $assigners = $mr->get_assigners($newmission->get_id());
        $this->assertCount(1, $assigners);
        $this->assertEquals(group_members::class, get_class($assigners[0]->get_type()));
        $this->assertEquals([$newg1->id], $assigners[0]->get_type_config()->groupids);

        $objectives = $mr->get_objectives($newmission->get_id());
        $this->assertCount(1, $objectives);
        $this->assertEquals(complete_activity::class, get_class($objectives[0]->get_type()));
        $this->assertEquals($newpage->id, $objectives[0]->get_type_config()->cmid);

        $outcomes = $mr->get_outcomes($newmission->get_id());
        $this->assertCount(1, $outcomes);
        $this->assertEquals(add_to_group::class, get_class($outcomes[0]->get_type()));
        $this->assertEquals($newg2->id, $outcomes[0]->get_type_config()->groupid);
    }

    /**
     * Count mission instances in context.
     *
     * @param int $contextid The context ID.
     */
    protected function count_mission_instances($contextid) {
        global $DB;
        return $DB->count_records_select('block_gearup_mission_inst',
            'missionid IN (SELECT id FROM {block_gearup_mission} WHERE contextid = ?)',
            [$contextid]
        );
    }

    /**
     * Get mission instances in context.
     *
     * @param int $contextid The context ID.
     * @param string $title The title.
     * @return \stdClass
     */
    protected function get_mission_instance_by_title($contextid, $iteration, $title) {
        global $DB;
        return $DB->get_record_select('block_gearup_mission_inst',
            'iteration = ?
            AND missionid IN (SELECT id FROM {block_gearup_mission} WHERE contextid = ?)
            AND missionid IN (SELECT id FROM {block_gearup_mission} WHERE title = ?)',
            [$iteration, $contextid, $title],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Setup the courses.
     *
     * @return array
     */
    protected function setup_courses() {
        global $DB;

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $u3 = $dg->create_user();

        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();

        $que1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_QUEST,
            'startmode' => mission::START_OPTIN,
            'contextid' => $c1ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 8]],
        ]);
        $que2a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_QUEST,
            'startmode' => mission::START_OPTIN,
            'contextid' => $c2ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 81]],
        ]);
        $ach1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_ACHIEVEMENT,
            'contextid' => $c1ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 10]],
        ]);
        $ach2a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_ACHIEVEMENT,
            'contextid' => $c2ctx->id,
            'objectives' => [['type' => 'manual', 'countneeded' => 101]],
        ]);
        $cha1a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_CHALLENGE,
            'contextid' => $c1ctx->id,
            'repeatcount' => mission::REPEAT_ALWAYS,
            'timelimit' => DAYSECS,
            'objectives' => [['type' => 'manual', 'countneeded' => 16]],
        ]);
        $cha2a = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_CHALLENGE,
            'contextid' => $c2ctx->id,
            'repeatcount' => mission::REPEAT_ALWAYS,
            'timelimit' => DAYSECS,
            'objectives' => [['type' => 'manual', 'countneeded' => 16]],
        ]);

        $mique1aofu1 = $mo->assign_mission($que1a, $u1->id);
        $mique1aofu2 = $mo->assign_mission($que1a, $u2->id);
        $mique2aofu1 = $mo->assign_mission($que2a, $u1->id);

        $miach1aofu1 = $mo->assign_mission($ach1a, $u1->id);
        $miach1aofu2 = $mo->assign_mission($ach1a, $u2->id);
        $miach2aofu1 = $mo->assign_mission($ach2a, $u1->id);

        $micha1aofu1 = $mo->assign_mission($cha1a, $u1->id);
        $micha1aofu2 = $mo->assign_mission($cha1a, $u2->id);
        $micha2aofu1 = $mo->assign_mission($cha2a, $u1->id);

        // Start quest.
        $mo->start_instance($mique1aofu1);
        $obj = $mique1aofu1->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 6);
        $mo->evaluate_instance($mique1aofu1);

        // Complete quest.
        $mo->start_instance($mique1aofu2);
        $obj = $mique1aofu2->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 8);
        $mo->evaluate_instance($mique1aofu2);
        $mo->end_instance($mique1aofu2);

        // Increment one achievement.
        $obj = $miach1aofu1->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 2);
        $mo->evaluate_instance($miach1aofu1);

        // Complete one achievement.
        $obj = $miach1aofu2->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 10);
        $mo->evaluate_instance($miach1aofu2);

        // Iterate one challenge after fail at 50%.
        $obj = $micha1aofu1->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 8);
        $mo->evaluate_instance($micha1aofu1);
        $mo->end_instance($micha1aofu1);

        // Complete one challenge.
        $obj = $micha1aofu2->get_objective_instances()[0];
        $oo->increment_instance_counter($obj, 16);
        $mo->evaluate_instance($micha1aofu2);
        $mo->end_instance($micha1aofu2);

        // Validate and document setup.
        $this->assertEquals(6, $DB->count_records('block_gearup_mission', []));
        $this->assertEquals(3, $DB->count_records('block_gearup_mission', ['contextid' => $c1ctx->id]));
        $this->assertEquals(11, $DB->count_records('block_gearup_mission_inst', []));
        $this->assertEquals(2, $DB->count_records('block_gearup_mission_inst', ['missionid' => $que1a->get_id()]));
        $this->assertEquals(1, $DB->count_records('block_gearup_mission_inst', ['missionid' => $que2a->get_id()]));
        $this->assertEquals(2, $DB->count_records('block_gearup_mission_inst', ['missionid' => $ach1a->get_id()]));
        $this->assertEquals(1, $DB->count_records('block_gearup_mission_inst', ['missionid' => $ach2a->get_id()]));
        $this->assertEquals(4, $DB->count_records('block_gearup_mission_inst', ['missionid' => $cha1a->get_id()]));
        $this->assertEquals(1, $DB->count_records('block_gearup_mission_inst', ['missionid' => $cha2a->get_id()]));

        // Add block to courses.
        $this->add_block_to_course($c1->id);
        $this->add_block_to_course($c2->id);

        return [
            'c1' => $c1, 'c2' => $c2, 'u1' => $u1, 'u2' => $u2, 'u3' => $u3,

            'que1a' => $que1a,
            'que2a' => $que2a,
            'ach1a' => $ach1a,
            'ach2a' => $ach2a,
            'cha1a' => $cha1a,
            'cha2a' => $cha2a,

            'mique1aofu1' => $mique1aofu1,
            'mique1aofu2' => $mique1aofu2,
            'mique2aofu1' => $mique2aofu1,
            'miach1aofu1' => $miach1aofu1,
            'miach1aofu2' => $miach1aofu2,
            'miach2aofu1' => $miach2aofu1,
            'micha1aofu1' => $micha1aofu1,
            'micha1aofu2' => $micha1aofu2,
            'micha2aofu1' => $micha2aofu1,
        ];
    }

}
