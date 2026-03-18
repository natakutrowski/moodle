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

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\mission;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\mission as mission_model;
use block_gearup\local\model\objective_inst;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\operator\mission_operator
 */
final class operator_test extends base_testcase {

    /**
     * Test.
     */
    public function test_objective_update_after_add_delete(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_persisted_mission(['objectives' => [
            ['type' => 'manual', 'countneeded' => 2],
            ['type' => 'manual', 'countneeded' => 2],
        ]]);
        $objs = $mission->get_objectives();

        $miu1 = $mo->assign_mission($mission, $u1->id);

        $objinsts = $miu1->get_objective_instances();
        $oo->increment_instance_counter($objinsts[0], 1);
        $oo->increment_instance_counter($objinsts[1], 2);
        $mo->evaluate_instance($miu1);
        $this->assertEquals(1, $objinsts[0]->get_counter());
        $this->assertEquals(2, $objinsts[1]->get_counter());
        $this->assertFalse($objinsts[0]->is_completed());
        $this->assertTrue($objinsts[1]->is_completed());
        $this->assertEquals(0.75, $miu1->get_completion_ratio());

        // Let's delete the first objective.
        objective_inst::delete_by_objective_id($objs[0]->get_id());
        $objs[0]->get_persistent()->delete();

        // And add another one.
        $gudg->create_objective_model(['missionid' => $mission->get_id(), 'countneeded' => 8, 'type' => 'manual']);

        // Let's reload is all.
        $mission = $mr->get_mission($mission->get_id());
        $miu1 = $mr->get_instance($miu1->get_id());
        $mo->update_instance_objectives($miu1);

        // Validate changes.
        $this->assertEquals(2, count($mission->get_objectives()));
        $this->assertEquals(2, $mission->get_objectives()[0]->get_count_needed());
        $this->assertEquals(8, $mission->get_objectives()[1]->get_count_needed());

        $this->assertEquals(2, count($miu1->get_objective_instances()));
        $this->assertEquals(2, $miu1->get_objective_instances()[0]->get_objective()->get_count_needed());
        $this->assertEquals(8, $miu1->get_objective_instances()[1]->get_objective()->get_count_needed());
        $this->assertEquals(2, $miu1->get_objective_instances()[0]->get_counter());
        $this->assertEquals(0, $miu1->get_objective_instances()[1]->get_counter());
        $this->assertTrue($miu1->get_objective_instances()[0]->is_completed());
        $this->assertFalse($miu1->get_objective_instances()[1]->is_completed());
        $this->assertEquals(0.5, $miu1->get_completion_ratio());
    }

    /**
     * Test.
     */
    public function test_objective_config_update(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_persisted_mission(['objectives' => [
            ['type' => 'manual', 'countneeded' => 10],
            ['type' => 'manual', 'countneeded' => 6],
        ]]);
        $objs = $mission->get_objectives();

        $miu1 = $mo->assign_mission($mission, $u1->id);
        $miu2 = $mo->assign_mission($mission, $u2->id);

        $objinsts = $miu1->get_objective_instances();
        $oo->increment_instance_counter($objinsts[0], 8);
        $mo->evaluate_instance($miu1);
        $this->assertEquals(8, $objinsts[0]->get_counter());
        $this->assertEquals(0, $objinsts[1]->get_counter());
        $this->assertFalse($objinsts[0]->is_completed());
        $this->assertFalse($objinsts[1]->is_completed());
        $this->assertEquals(0.4, $miu1->get_completion_ratio());

        $objinsts = $miu2->get_objective_instances();
        $oo->increment_instance_counter($objinsts[1], 8);
        $mo->evaluate_instance($miu2);
        $this->assertEquals(0, $objinsts[0]->get_counter());
        $this->assertEquals(6, $objinsts[1]->get_counter()); // Capped at 6.
        $this->assertFalse($objinsts[0]->is_completed());
        $this->assertTrue($objinsts[1]->is_completed());
        $this->assertEquals(0.5, $miu2->get_completion_ratio());

        // Let's change the first objective.
        $objs[0]->get_persistent()->set('countneeded', 5);
        $objs[0]->get_persistent()->save();
        $objs[1]->get_persistent()->set('countneeded', 12);
        $objs[1]->get_persistent()->save();

        // Let's reload and update the objectives.
        $mission = $mr->get_mission($mission->get_id());
        $objs = $mission->get_objectives();
        $miu1 = $mr->get_instance($miu1->get_id());
        $miu2 = $mr->get_instance($miu2->get_id());
        $mo->update_instance_objectives($miu1);
        $mo->update_instance_objectives($miu2);

        // Validate changes.
        $this->assertEquals(5, $miu1->get_mission()->get_objective($objs[0]->get_id())->get_count_needed());
        $this->assertEquals(12, $miu1->get_mission()->get_objective($objs[1]->get_id())->get_count_needed());

        // Check that the objective 1 is now completed for user 1.
        $this->assertEquals(8, $miu1->get_objective_instances()[0]->get_counter());
        $this->assertTrue($miu1->get_objective_instances()[0]->is_completed());
        $this->assertEquals(0, $miu1->get_objective_instances()[1]->get_counter());
        $this->assertFalse($miu1->get_objective_instances()[1]->is_completed());
        $this->assertEquals(0.5, $miu1->get_completion_ratio());

        // Check that the objective 2 remains complete for user 2.
        $this->assertEquals(0, $miu2->get_objective_instances()[0]->get_counter());
        $this->assertFalse($miu2->get_objective_instances()[0]->is_completed());
        $this->assertEquals(6, $miu2->get_objective_instances()[1]->get_counter());
        $this->assertTrue($miu2->get_objective_instances()[1]->is_completed());
        $this->assertEquals(0.5, $miu1->get_completion_ratio());
    }

    /**
     * Test.
     */
    public function test_duplicate_mission(): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $gudg = $this->generator;

        $mission = $gudg->create_persisted_mission([
            'type' => mission_model::TYPE_QUEST,
            'title' => 'Copy me',
            'description' => 'Describe me',
            'feedback' => 'Feedback me',
            'instructions' => 'Do this',
            'repeatcount' => mission::REPEAT_ALWAYS,
            'startmode' => mission::START_OPTIN,
            'timelimit' => 3600,
            'visibility' => mission::VISIBLE_SECRET,
            'visual' => 'fake-image',
            'objectives' => [
                ['type' => 'manual', 'countneeded' => 1, 'label' => 'Obj A', 'configdata' => ['foo' => 'bar']],
                ['type' => 'access_platform', 'countneeded' => 2, 'label' => 'Obj B', 'configdata' => ['test' => 1]],
            ],
            'outcomes' => [
                ['type' => 'label', 'label' => 'Nothing for you!', 'visibility' => 0, 'configdata' => ['deez' => 'nuts']],
                ['type' => 'notification', 'label' => 'You did it!', 'visibility' => 1, 'configdata' =>
                    ['subject' => 'a', 'message' => 'b']],
            ],
            'assigners' => [
                ['type' => 'everyone', 'configdata' => ['join' => 'me']],
                ['type' => 'cohort_members', 'configdata' => ['cohortid' => '123']],
            ],
        ]);

        $dupedmission = $mo->duplicate_mission($mission);

        // Validate mission properties.
        $this->assertGreaterThan(0, $mission->get_id());
        $this->assertGreaterThan(0, $dupedmission->get_id());
        $this->assertNotEquals($mission->get_id(), $dupedmission->get_id());
        $this->assertEquals(get_class($mission), get_class($dupedmission));
        $samemethods = ['get_title', 'get_description', 'get_feedback', 'get_instructions', 'get_repeat_count',
            'get_start_mode', 'get_time_limit', 'get_visibility'];
        foreach ($samemethods as $method) {
            $this->assertSame($mission->$method(), $dupedmission->$method());
        }
        $this->assertSame($mission->get_persistent()->get('visual'), $dupedmission->get_persistent()->get('visual'));

        // Validate objectives.
        $objs = $mr->get_objectives($mission->get_id());
        $dupedobjs = $mr->get_objectives($dupedmission->get_id());
        $samemethods = ['get_type', 'get_count_needed', 'get_label'];
        $this->assertCount(count($objs), $dupedobjs);
        for ($i = 0; $i < count($objs); $i++) {
            $obj = $objs[$i];
            $dupedobj = $dupedobjs[$i];
            $this->assertNotEquals($obj->get_id(), $dupedobj->get_id());
            $this->assertNotEquals($obj->get_mission_id(), $dupedobj->get_mission_id());
            $this->assertEquals($obj->get_type_config(), $dupedobj->get_type_config());
            foreach ($samemethods as $method) {
                $this->assertSame($objs[$i]->$method(), $dupedobjs[$i]->$method(), 'Failed with ' . $method);
            }
            $this->assert_persistent_equals($obj->get_persistent(),
                $dupedobj->get_persistent(),
                ['id', 'timecreated', 'timemodified', 'missionid']
            );
        }

        // Validate outcomes.
        $objs = $mr->get_outcomes($mission->get_id());
        $dupedobjs = $mr->get_outcomes($dupedmission->get_id());
        $samemethods = ['get_type', 'get_label'];
        $this->assertCount(count($objs), $dupedobjs);
        for ($i = 0; $i < count($objs); $i++) {
            $obj = $objs[$i];
            $dupedobj = $dupedobjs[$i];
            $this->assertNotEquals($obj->get_id(), $dupedobj->get_id());
            $this->assertNotEquals($obj->get_persistent()->get('missionid'), $dupedobj->get_persistent()->get('missionid'));
            $this->assertEquals($obj->get_type_config(), $dupedobj->get_type_config());
            foreach ($samemethods as $method) {
                $this->assertSame($objs[$i]->$method(), $dupedobjs[$i]->$method());
            }
            $this->assert_persistent_equals($obj->get_persistent(),
                $dupedobj->get_persistent(),
                ['id', 'timecreated', 'timemodified', 'missionid']
            );
        }

        // Validate assigners.
        $objs = $mr->get_assigners($mission->get_id());
        $dupedobjs = $mr->get_assigners($dupedmission->get_id());
        $samemethods = ['get_type', 'get_label', 'is_enabled'];
        $this->assertCount(count($objs), $dupedobjs);
        for ($i = 0; $i < count($objs); $i++) {
            $obj = $objs[$i];
            $dupedobj = $dupedobjs[$i];
            $this->assertNotEquals($obj->get_id(), $dupedobj->get_id());
            $this->assertNotEquals($obj->get_persistent()->get('missionid'), $dupedobj->get_persistent()->get('missionid'));
            $this->assertEquals($obj->get_type_config(), $dupedobj->get_type_config());
            foreach ($samemethods as $method) {
                $this->assertSame($objs[$i]->$method(), $dupedobjs[$i]->$method());
            }
            $this->assert_persistent_equals($obj->get_persistent(),
                $dupedobj->get_persistent(),
                ['id', 'timecreated', 'timemodified', 'missionid']
            );
        }
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function duplicate_mission_with_include_options_provider(): array {
        $missiondata = [
            'objectives' => [['type' => 'manual']],
            'outcomes' => [['type' => 'label']],
            'assigners' => [['type' => 'everyone']],
        ];
        return [
            [$missiondata, [], [1, 1, 1]],
            [$missiondata, ['includeobjectives' => true, 'includeoutcomes' => true, 'includeassigners' => true], [1, 1, 1]],
            [$missiondata, ['includeobjectives' => false, 'includeoutcomes' => true, 'includeassigners' => true], [0, 1, 1]],
            [$missiondata, ['includeobjectives' => true, 'includeoutcomes' => false, 'includeassigners' => true], [1, 0, 1]],
            [$missiondata, ['includeobjectives' => true, 'includeoutcomes' => true, 'includeassigners' => false], [1, 1, 0]],
            [$missiondata, ['includeobjectives' => false, 'includeoutcomes' => true, 'includeassigners' => false], [0, 1, 0]],
            [$missiondata, ['includeobjectives' => false, 'includeoutcomes' => false, 'includeassigners' => true], [0, 0, 1]],
            [$missiondata, ['includeobjectives' => false, 'includeoutcomes' => false, 'includeassigners' => false], [0, 0, 0]],
            [$missiondata, ['includeobjectives' => false], [0, 1, 1]],
            [$missiondata, ['includeoutcomes' => false], [1, 0, 1]],
            [$missiondata, ['includeassigners' => false], [1, 1, 0]],
        ];
    }

    /**
     * Duplicate mission with options.
     *
     * @dataProvider duplicate_mission_with_include_options_provider
     * @param mixed $data The data.
     * @param mixed $expected The expected.
     */
    public function test_duplicate_mission_with_include_options($missiondata, $options, $expected): void {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');
        $gudg = $this->generator;

        $mission = $gudg->create_persisted_mission($missiondata);
        $dupedmission = $mo->duplicate_mission($mission, $options);

        [$expectedobjs, $expectedoutcomes, $expectedassigners] = $expected;

        $dupedobjs = $mr->get_objectives($dupedmission->get_id());
        $this->assertCount($expectedobjs, $dupedobjs);

        $dupedobjs = $mr->get_outcomes($dupedmission->get_id());
        $this->assertCount($expectedoutcomes, $dupedobjs);

        $dupedobjs = $mr->get_assigners($dupedmission->get_id());
        $this->assertCount($expectedassigners, $dupedobjs);
    }

    /**
     * Test assigning a mission.
     */
    public function test_assign_mission(): void {

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $mo = di::get('mission_operator');
        $u = $dg->create_user();

        $mission = $gudg->create_persisted_mission([
            'state' => mission::STATE_ACTIVE,
            'objectives' => [['type' => 'manual']],
        ]);
        $mi = $mo->assign_mission($mission, $u->id);
        $this->assertInstanceOf(mission_instance::class, $mi);
        $this->assertEquals($mission->get_id(), $mi->get_mission()->get_id());
        $this->assertEquals($u->id, $mi->get_subject_id());
    }

    /**
     * Test assigning a draft mission.
     */
    public function test_assign_draft_mission(): void {

        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $mo = di::get('mission_operator');
        $u = $dg->create_user();

        $mission = $gudg->create_persisted_mission([
            'state' => mission::STATE_WIZARD,
            'objectives' => [['type' => 'manual']],
        ]);
        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessageMatches('/not active/');

        $mo->assign_mission($mission, $u->id);
    }

}
