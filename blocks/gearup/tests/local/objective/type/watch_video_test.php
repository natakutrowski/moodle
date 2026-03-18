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

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\di;
use block_gearup\local\action\time_watched;
use block_gearup\local\action\video_watched;
use block_gearup\local\repository\mission_query;
use block_gearup\tests\base_testcase;
use context_course;
use context_module;
use restore_dbops;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\watch_video
 */
final class watch_video_test extends base_testcase {

    public function test_passing_constraints(): void {
        $gudg = $this->generator;
        $u1 = $this->getDataGenerator()->create_user();

        $type = new watch_video();
        $obj = $gudg->mock_objective($type, []);
        $mission = $gudg->mock_achievement(['objectives' => [$obj]]);
        $objinst = $gudg->mock_objective_instance($obj, $u1);
        $missioninst = $gudg->mock_mission_instance($mission, $u1, ['objinsts' => [$objinst]]);

        $action = new video_watched(0, \context_system::instance(), 'example');
        $this->assertTrue($type->is_action_passing_constraints($action, $objinst, $missioninst));
        $action = new time_watched(0, \context_system::instance(), 10, 'example');
        $this->assertFalse($type->is_action_passing_constraints($action, $objinst, $missioninst));
    }

    public function test_passing_constraints_skips_watched(): void {
        $gudg = $this->generator;
        $u1 = $this->getDataGenerator()->create_user();

        $type = new watch_video();
        $obj = $gudg->mock_objective($type, []);
        $mission = $gudg->mock_achievement(['objectives' => [$obj]]);
        $objinst = $gudg->mock_objective_instance($obj, $u1);
        $missioninst = $gudg->mock_mission_instance($mission, $u1, ['objinsts' => [$objinst]]);

        $action = new video_watched(0, \context_system::instance(), 'example');
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());
        $this->assertFalse($type->is_action_passing_constraints($action, $objinst, $missioninst));
    }

    public function test_passing_constraints_observes_cmid(): void {
        $gudg = $this->generator;
        $c1 = $this->getDataGenerator()->create_course();
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $c1->id]);
        $page2 = $this->getDataGenerator()->create_module('page', ['course' => $c1->id]);
        $u1 = $this->getDataGenerator()->create_user();

        $type = new watch_video();
        $obj = $gudg->mock_objective($type, []);
        $mission = $gudg->mock_achievement(['objectives' => [$obj]]);
        $objinst = $gudg->mock_objective_instance($obj, $u1);
        $missioninst = $gudg->mock_mission_instance($mission, $u1, ['objinsts' => [$objinst]]);

        $action1 = new video_watched(0, \context_module::instance($page1->cmid), 'example1');
        $action2 = new video_watched(0, \context_module::instance($page2->cmid), 'example2');
        $action3 = new video_watched(0, \context_course::instance($c1->id), 'example3');

        // All accepted without config.
        $this->assertTrue($type->is_action_passing_constraints($action1, $objinst, $missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2, $objinst, $missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action3, $objinst, $missioninst));

        $obj = $gudg->mock_objective($type, ['cmid' => $page2->cmid]);
        $mission = $gudg->mock_achievement(['objectives' => [$obj]]);
        $objinst = $gudg->mock_objective_instance($obj, $u1);
        $missioninst = $gudg->mock_mission_instance($mission, $u1, ['objinsts' => [$objinst]]);

        // Only page 2 accepted.
        $this->assertFalse($type->is_action_passing_constraints($action1, $objinst, $missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2, $objinst, $missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action3, $objinst, $missioninst));
    }

    public function test_upgrade_after_restore(): void {
        $mr = di::get('repository');
        $gudg = $this->generator;
        $c1 = $this->getDataGenerator()->create_course();
        $c1ctx = context_course::instance($c1->id);
        $page1 = $this->getDataGenerator()->create_module('page', ['course' => $c1->id]);

        $this->add_block_to_course($c1->id);
        $achievement = $gudg->create_achievement([
            'contextid' => $c1ctx->id,
            'objectives' => [[
                'type' => 'watch_video',
                'configdata' => ['cmid' => $page1->cmid],
            ]],
        ]);

        $this->setAdminUser();
        $backupid = $this->backup($c1);

        $newid = restore_dbops::create_new_course($c1->fullname . 'new', $c1->shortname . 'new', $c1->category);
        $newctx = context_course::instance($newid);
        $this->restore($backupid, $newid, backup::TARGET_NEW_COURSE, ['users' => false]);

        $missions = iterator_to_array($mr->get_missions_from_query((new mission_query($newctx))->set_context_id($newctx->id)));
        $this->assertCount(1, $missions);
        $mission = reset($missions)->mission;
        $this->assertNotEquals($achievement->get_objectives()[0]->get_id(), $mission->get_objectives()[0]->get_id());
        $this->assertNotEquals(
            $achievement->get_objectives()[0]->get_type_config()->cmid,
            $mission->get_objectives()[0]->get_type_config()->cmid
        );
        $page2ctx = context_module::instance($mission->get_objectives()[0]->get_type_config()->cmid);
        $this->assertEquals($newid, $page2ctx->get_course_context()->instanceid);
    }

}
