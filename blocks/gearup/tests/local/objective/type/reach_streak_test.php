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

use block_gearup\local\action\streak_reached;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\streak;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\mission\quest;
use block_gearup\local\objective\type\reach_streak;
use block_gearup\tests\base_testcase;
use context_course;
use context_system;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\reach_streak
 */
final class reach_streak_test extends base_testcase {

    /** @var mission */
    protected $mission;
    /** @var mission_instance */
    protected $missioninst;
    /** @var streak */
    protected $streak;

    /**
     * Test.
     */
    public function test_basic(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $streak = $gudg->mock_mission_instance($gudg->mock_streak(), ['counter' => 10]);
        $action = new streak_reached($streak);

        $type = new reach_streak();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest());
        $obj = $gudg->mock_objective($type, null, ['countneeded' => 10]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertEquals(0, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());
        $type->consume_action($action, $objinst, $missioninst);
        $this->assertEquals(2, $objinst->get_counter());
    }


    /**
     * Data provider.
     *
     * @return array
     */
    public static function counter_constraint(): array {
        return [
            [5, 10, true],
            [9, 10, true],
            [10, 10, true],
            [11, 10, false],
            [20, 10, false],
            [0, 0, false],
            [1, 0, false],
            [2, 0, false],
            [0, 1, true],
            [1, 1, true],
        ];
    }

    /**
     * Test counter constraints.
     *
     * @param int $needed Streak needed.
     * @param int $current Current streak.
     * @param bool $expected Expected result.
     * @dataProvider counter_constraint
     */
    public function test_counter_constraints($needed, $current, $expected): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $streak = $gudg->mock_mission_instance($gudg->mock_streak(), ['counter' => $current]);
        $action = new streak_reached($streak);

        $type = new reach_streak();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest());
        $obj = $gudg->mock_objective($type, ['streak' => $needed]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);
        $this->assertSame($expected, $type->is_action_passing_constraints($action, $objinst, $missioninst));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function which_constraints(): array {
        return [
            ['sys', 'sys', reach_streak::WHICH_ANY, true],
            ['sys', 'c1', reach_streak::WHICH_ANY, true],
            ['sys', 'c2', reach_streak::WHICH_ANY, true],
            ['c1', 'c1', reach_streak::WHICH_ANY, true],
            ['c1', 'c2', reach_streak::WHICH_ANY, false],
            ['c1', 'sys', reach_streak::WHICH_ANY, true],

            ['sys', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, true],
            ['sys', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['sys', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, true],
            ['c1', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, false],
        ];
    }

    /**
     * Test constraints with which `any`.
     *
     * @param string $context The context.
     * @param string $actioncontext The action context.
     * @param int $which The which constraint.
     * @param bool $expected Expected result.
     * @dataProvider which_constraints
     */
    public function test_which_constraints($context, $actioncontext, $which, $expected): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $sysctx = context_system::instance();

        if ($context === 'sys') {
            $context = $sysctx;
        } else if ($context === 'c1') {
            $context = $c1ctx;
        } else if ($context === 'c2') {
            $context = $c2ctx;
        } else {
            throw new \coding_exception('Invalid provider param');
        }

        if ($actioncontext === 'sys') {
            $actioncontext = $sysctx;
        } else if ($actioncontext === 'c1') {
            $actioncontext = $c1ctx;
        } else if ($actioncontext === 'c2') {
            $actioncontext = $c2ctx;
        } else {
            throw new \coding_exception('Invalid provider param');
        }

        $type = new reach_streak();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest(['context' => $context]));
        $obj = $gudg->mock_objective($type, ['streak' => 5, 'which' => $which]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        $streakmission = $gudg->mock_streak(['context' => $actioncontext]);
        $streak = $gudg->mock_mission_instance($streakmission, ['counter' => 10]);
        $action = new streak_reached($streak);
        $this->assertSame($expected, $type->is_action_passing_constraints($action, $objinst, $missioninst));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function initialise_state_which(): array {
        return [
            ['sys', 'sys', reach_streak::WHICH_ANY, true],
            ['sys', 'c1', reach_streak::WHICH_ANY, true],
            ['sys', 'c2', reach_streak::WHICH_ANY, true],
            ['c1', 'c1', reach_streak::WHICH_ANY, true],
            ['c1', 'c2', reach_streak::WHICH_ANY, false],
            ['c1', 'sys', reach_streak::WHICH_ANY, true],

            ['sys', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, true],
            ['sys', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['sys', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, true],
            ['c1', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, false],
        ];
    }

    /**
     * Test constraints with which `any`.
     *
     * @param string $context The context.
     * @param string $streakcontext The existing streak context.
     * @param int $which The which constraint.
     * @param bool $isincreased Whether we expect an increase.
     * @dataProvider initialise_state_which
     */
    public function test_initialise_state_which($context, $streakcontext, $which, $isincreased): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $sysctx = context_system::instance();

        if ($context === 'sys') {
            $context = $sysctx;
        } else if ($context === 'c1') {
            $context = $c1ctx;
        } else if ($context === 'c2') {
            $context = $c2ctx;
        } else {
            throw new \coding_exception('Invalid provider param');
        }

        if ($streakcontext === 'sys') {
            $streakcontext = $sysctx;
        } else if ($streakcontext === 'c1') {
            $streakcontext = $c1ctx;
        } else if ($streakcontext === 'c2') {
            $streakcontext = $c2ctx;
        } else {
            throw new \coding_exception('Invalid provider param');
        }

        $type = new reach_streak();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest(['context' => $context]));
        $obj = $gudg->mock_objective($type, ['streak' => 10, 'which' => $which]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        // First nothing exists.
        $this->assertEquals(0, $objinst->get_counter());
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create the existing instance in context.
        $streakmission = $gudg->create_streak([
            'contextid' => $streakcontext->id,
        ]);
        $streakmissioninst = $gudg->create_persisted_mission_instance($streakmission, [
            'subjectid' => 2,
            'state' => mission_instance::STATE_STARTED,
            'timestarted' => time(),
            'counter' => 10,
        ]);

        // And we validate that it has increased, or not.
        $prevcounter = $objinst->get_counter();
        $type->initialise_state($objinst, $missioninst);
        if (!$isincreased) {
            $this->assertSame($prevcounter, $objinst->get_counter());
        } else {
            $this->assertGreaterThan($prevcounter, $objinst->get_counter());
        }
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function initialise_state_with_counter_type_and_state(): array {
        return [
            [10, 10, streak::class, mission_instance::STATE_ASSIGNED, true],
            [10, 10, streak::class, mission_instance::STATE_STARTED, true],
            [10, 10, streak::class, mission_instance::STATE_COMPLETED, true],
            [10, 10, streak::class, mission_instance::STATE_ENDED, false],

            [10, 0, streak::class, mission_instance::STATE_STARTED, false],
            [10, 5, streak::class, mission_instance::STATE_STARTED, false],
            [10, 9, streak::class, mission_instance::STATE_STARTED, false],
            [10, 11, streak::class, mission_instance::STATE_STARTED, true],
            [10, 20, streak::class, mission_instance::STATE_STARTED, true],

            [10, 20, quest::class, mission_instance::STATE_STARTED, false],
        ];
    }

    /**
     * Test constraints with which `any`.
     *
     * @param int $needed The streak needed.
     * @param int $current The current streak.
     * @param string $missiontype The mission type.
     * @param int $state The mission instance state.
     * @param bool $isincreased Whether we expect an increase.
     * @dataProvider initialise_state_with_counter_type_and_state
     */
    public function test_initialise_state_with_counter_type_and_state($needed, $current, $missiontype, $state, $isincreased): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $sysctx = context_system::instance();

        $type = new reach_streak();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest(['context' => $sysctx]));
        $obj = $gudg->mock_objective($type, ['streak' => $needed, 'which' => reach_streak::WHICH_ANY]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        // First nothing exists.
        $this->assertEquals(0, $objinst->get_counter());
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create the existing instance in context.
        $streakmission = $gudg->create_persisted_mission([
            'type' => $missiontype,
            'contextid' => $sysctx->id,
        ]);
        $streakmissioninst = $gudg->create_persisted_mission_instance($streakmission, [
            'subjectid' => 2,
            'state' => $state,
            'counter' => $current,
        ]);

        // And we validate that it has increased, or not.
        $prevcounter = $objinst->get_counter();
        $type->initialise_state($objinst, $missioninst);
        if (!$isincreased) {
            $this->assertSame($prevcounter, $objinst->get_counter());
        } else {
            $this->assertGreaterThan($prevcounter, $objinst->get_counter());
        }
    }

}
