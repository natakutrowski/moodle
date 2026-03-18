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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\activity_completed;
use block_gearup\local\objective\type\complete_activity;
use block_gearup\tests\base_testcase;
use context_module;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\complete_activity
 */
final class complete_activity_test extends base_testcase {

    protected $mission;
    protected $missioninst;

    public function setUp(): void {
        parent::setUp();
        $gudg = $this->generator;
        $this->mission = $gudg->mock_quest();
        $this->missioninst = $gudg->mock_mission_instance($this->mission);
    }

    /**
     * Test.
     */
    public function test_basic(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $cm1 = $dg->create_module('page', ['course' => $c1]);
        $action = new activity_completed(2, context_module::instance($cm1->cmid));

        $type = new complete_activity();
        $obj = $gudg->mock_objective($type, null, ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $this->missioninst);
        $this->assertEquals(1, $objinst->get_counter());
        $type->consume_action($action, $objinst, $this->missioninst);
        $this->assertEquals(2, $objinst->get_counter());
    }

    /**
     * Test.
     */
    public function test_tracking(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $cm1 = $dg->create_module('page', ['course' => $c1]);
        $cm2 = $dg->create_module('page', ['course' => $c1, 'section' => 2]);
        $action = new activity_completed(2, context_module::instance($cm1->cmid));
        $action2 = new activity_completed(2, context_module::instance($cm2->cmid));

        $type = new complete_activity();

        // No tracking when count needed is 1.
        $obj = $gudg->mock_objective($type, null, ['countneeded' => 1]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);
        $type->consume_action($action, $objinst, $this->missioninst);
        $this->assertEquals(1, $objinst->get_counter());
        $this->assertEquals(null, $objinst->get_type_state());

        // Always track when count needed is greater than 1.
        $obj = $gudg->mock_objective($type, null, ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        $type->consume_action($action, $objinst, $this->missioninst);
        $this->assertEquals(1, $objinst->get_counter());
        $this->assertEquals((object) ['seen' => [['courseid' => $action->get_course_id(), 'section' => 0]]],
            $objinst->get_type_state()
        );

            $type->consume_action($action2, $objinst, $this->missioninst);
        $this->assertEquals((object) ['seen' => [
                ['courseid' => $action->get_course_id(), 'section' => 0],
                ['courseid' => $action->get_course_id(), 'section' => 2]],
            ], $objinst->get_type_state());
    }

    /**
     * Test.
     */
    public function test_is_action_passing_constraints_which_any(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();

        $cm1a = $dg->create_module('page', ['course' => $c1]);
        $cm1b = $dg->create_module('page', ['course' => $c1, 'section' => 2]);
        $cm1c = $dg->create_module('page', ['course' => $c1, 'section' => 2]);
        $cm2a = $dg->create_module('page', ['course' => $c2]);

        $action1a = new activity_completed($u1->id, context_module::instance($cm1a->cmid));
        $action1b = new activity_completed($u1->id, context_module::instance($cm1b->cmid));
        $action1c = new activity_completed($u1->id, context_module::instance($cm1c->cmid));
        $action2a = new activity_completed($u1->id, context_module::instance($cm2a->cmid));

        $type = new complete_activity();

        // No uniqueness.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_ANY,
            'uniqueness' => complete_activity::UNIQUENESS_DISABLED], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $u1->id]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
        $type->consume_action($action1a, $objinst, $this->missioninst);
        $type->consume_action($action1b, $objinst, $this->missioninst);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));

        // Uniqueness per course.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_ANY,
            'uniqueness' => complete_activity::UNIQUENESS_PER_COURSE], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $u1->id]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
        $type->consume_action($action1a, $objinst, $this->missioninst);
        $this->assertFalse($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));

        // Uniqueness per section.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_ANY,
            'uniqueness' => complete_activity::UNIQUENESS_PER_SECTION], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $u1->id]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
        $type->consume_action($action1a, $objinst, $this->missioninst);
        $this->assertFalse($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
        $type->consume_action($action1b, $objinst, $this->missioninst);
        $this->assertFalse($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
    }

    /**
     * Test.
     */
    public function test_is_action_passing_constraints_which_any_in_course(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();

        $cm1a = $dg->create_module('page', ['course' => $c1]);
        $cm1b = $dg->create_module('page', ['course' => $c1, 'section' => 2]);
        $cm1c = $dg->create_module('page', ['course' => $c1, 'section' => 2]);
        $cm1d = $dg->create_module('page', ['course' => $c2]);
        $cm2a = $dg->create_module('page', ['course' => $c2]);

        $action1a = new activity_completed($u1->id, context_module::instance($cm1a->cmid));
        $action1b = new activity_completed($u1->id, context_module::instance($cm1b->cmid));
        $action1c = new activity_completed($u1->id, context_module::instance($cm1c->cmid));
        $action2a = new activity_completed($u1->id, context_module::instance($cm2a->cmid));

        $type = new complete_activity();

        // No uniqueness.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_ANY_IN_COURSE, 'courseid' => $c1->id,
            'uniqueness' => complete_activity::UNIQUENESS_DISABLED], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $u1->id]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));
        $type->consume_action($action1a, $objinst, $this->missioninst);
        $type->consume_action($action1b, $objinst, $this->missioninst);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action2a, $objinst, $this->missioninst));

        // Uniqueness per section.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_ANY_IN_COURSE, 'courseid' => $c1->id,
            'uniqueness' => complete_activity::UNIQUENESS_PER_SECTION], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => $u1->id]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $type->consume_action($action1a, $objinst, $this->missioninst);
        $this->assertFalse($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertTrue($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
        $type->consume_action($action1b, $objinst, $this->missioninst);
        $this->assertFalse($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1c, $objinst, $this->missioninst));
    }

    /**
     * Test.
     */
    public function test_is_action_passing_constraints_which_specific(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $c1 = $dg->create_course();

        $cm1a = $dg->create_module('page', ['course' => $c1]);
        $cm1b = $dg->create_module('page', ['course' => $c1, 'section' => 2]);

        $action1a = new activity_completed($u1->id, context_module::instance($cm1a->cmid));
        $action1b = new activity_completed($u1->id, context_module::instance($cm1b->cmid));

        $type = new complete_activity();

        // No uniqueness.
        $obj = $gudg->mock_objective($type, ['which' => complete_activity::WHICH_SPECIFIC_IN_COURSE, 'courseid' => $c1->id,
            'cmid' => $cm1a->cmid, 'uniqueness' => complete_activity::UNIQUENESS_DISABLED], ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);
        $this->assertTrue($type->is_action_passing_constraints($action1a, $objinst, $this->missioninst));
        $this->assertFalse($type->is_action_passing_constraints($action1b, $objinst, $this->missioninst));
    }

}
