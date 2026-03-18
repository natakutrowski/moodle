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

use block_gearup\local\action\challenge_completed;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\type\complete_challenge;
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
 * @covers \block_gearup\local\objective\type\complete_challenge
 */
final class complete_challenge_test extends base_testcase {

    /**
     * Basic test.
     */
    public function test_basic(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $challenge = $gudg->mock_mission_instance($gudg->mock_challenge(), ['counter' => 10]);
        $action = new challenge_completed($challenge);

        $type = new complete_challenge();
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
    public static function which_constraints(): array {
        return [
            // C1 with c2 that resolves to true is questionnable as we should not really
            // allow cross context constraints, although it will be filtered elsewhere.
            ['sys', 'sys', 1, complete_challenge::WHICH_ANY, null, true],
            ['sys', 'c1', 1, complete_challenge::WHICH_ANY, null, true],
            ['sys', 'c2', 1, complete_challenge::WHICH_ANY, null, true],
            ['c1', 'c1', 1, complete_challenge::WHICH_ANY, null, true],
            ['c1', 'c2', 1, complete_challenge::WHICH_ANY, null, true],
            ['c1', 'sys', 1, complete_challenge::WHICH_ANY, null, true],

            ['sys', 'sys', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, true],
            ['sys', 'c1', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, false],
            ['sys', 'c2', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, false],
            ['c1', 'c1', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, true],
            ['c1', 'c2', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, false],
            ['c1', 'sys', 1, complete_challenge::WHICH_ANY_IN_CONTEXT, null, false],

            ['sys', 'sys', 123, complete_challenge::WHICH_SPECIFIC, 456, false],
            ['sys', 'sys', 123, complete_challenge::WHICH_SPECIFIC, 123, true],
            ['sys', 'c1', 123, complete_challenge::WHICH_SPECIFIC, 456, false],
            ['sys', 'c1', 123, complete_challenge::WHICH_SPECIFIC, 123, true],
            ['c1', 'c1', 123, complete_challenge::WHICH_SPECIFIC, 456, false],
            ['c1', 'c1', 123, complete_challenge::WHICH_SPECIFIC, 123, true],
            ['c1', 'c2', 123, complete_challenge::WHICH_SPECIFIC, 456, false],
            ['c1', 'c2', 123, complete_challenge::WHICH_SPECIFIC, 123, true],
            ['c1', 'sys', 123, complete_challenge::WHICH_SPECIFIC, 456, false],
            ['c1', 'sys', 123, complete_challenge::WHICH_SPECIFIC, 123, true],
        ];
    }

    /**
     * Test constraints with which `any`.
     *
     * @param string $context The context.
     * @param string $actioncontext The action context.
     * @param int $completedchallengeid The completed challenge ID.
     * @param int $which The which constraint.
     * @param int|null $wantedchallengeid The wanted challenge ID.
     * @param bool $expected Expected result.
     * @dataProvider which_constraints
     */
    public function test_which_constraints(
        $context,
        $actioncontext,
        int $completedchallengeid,
        int $which,
        ?int $wantedchallengeid,
        $expected
    ): void {
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

        $type = new complete_challenge();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest(['context' => $context]));
        $obj = $gudg->mock_objective($type, ['missionid' => $wantedchallengeid, 'which' => $which]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        $challengemission = $gudg->mock_challenge(['id' => $completedchallengeid, 'context' => $actioncontext]);
        $challenge = $gudg->mock_mission_instance($challengemission);
        $action = new challenge_completed($challenge);
        $this->assertSame($expected, $type->is_action_passing_constraints($action, $objinst, $missioninst));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public static function initialise_state_which_not_specific(): array {
        return [
            ['sys', 'sys', reach_streak::WHICH_ANY, false],
            ['sys', 'c1', reach_streak::WHICH_ANY, false],
            ['sys', 'c2', reach_streak::WHICH_ANY, false],
            ['c1', 'c1', reach_streak::WHICH_ANY, false],
            ['c1', 'c2', reach_streak::WHICH_ANY, false],
            ['c1', 'sys', reach_streak::WHICH_ANY, false],

            ['sys', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['sys', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['sys', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'c1', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'c2', reach_streak::WHICH_ANY_IN_CONTEXT, false],
            ['c1', 'sys', reach_streak::WHICH_ANY_IN_CONTEXT, false],
        ];
    }

    /**
     * Test constraints with which `any`.
     *
     * @param string $context The context.
     * @param string $challengecontext The existing challenge context.
     * @param int $which The which constraint.
     * @param bool $isincreased Whether we expect an increase.
     * @dataProvider initialise_state_which_not_specific
     */
    public function test_initialise_state_which_not_specific($context, $challengecontext, $which, $isincreased): void {

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

        if ($challengecontext === 'sys') {
            $challengecontext = $sysctx;
        } else if ($challengecontext === 'c1') {
            $challengecontext = $c1ctx;
        } else if ($challengecontext === 'c2') {
            $challengecontext = $c2ctx;
        } else {
            throw new \coding_exception('Invalid provider param');
        }

        $type = new complete_challenge();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest(['context' => $context]));
        $obj = $gudg->mock_objective($type, ['which' => $which]);
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        // First nothing exists.
        $this->assertEquals(0, $objinst->get_counter());
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create a completed instance in context, it will be ignored.
        $challengemission = $gudg->create_challenge([
            'contextid' => $challengecontext->id,
        ]);
        $gudg->create_persisted_mission_instance($challengemission, [
            'subjectid' => 2,
            'state' => mission_instance::STATE_COMPLETED,
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
     * Test constraints with which `specific`.
     */
    public function test_initialise_state_which_specific(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;
        $clock = $this->get_frozen_clock();

        $cha1 = $gudg->create_challenge();
        $cha2 = $gudg->create_challenge();

        $type = new complete_challenge();
        $missioninst = $gudg->mock_mission_instance($gudg->mock_quest());
        $obj = $gudg->mock_objective($type,
            ['missionid' => $cha1->get_id(), 'which' => complete_challenge::WHICH_SPECIFIC],
            ['countneeded' => 10]
        );
        $objinst = $gudg->mock_objective_instance($obj, ['subjectid' => 2]);

        // First nothing exists.
        $this->assertEquals(0, $objinst->get_counter());
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create a completed instance of another challenge.
        $gudg->create_persisted_mission_instance($cha2, [
            'subjectid' => 2,
            'state' => mission_instance::STATE_COMPLETED,
            'counter' => 10,
        ]);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create a completed instance of the same challenge for someone else.
        $gudg->create_persisted_mission_instance($cha2, [
            'subjectid' => 3,
            'state' => mission_instance::STATE_COMPLETED,
            'counter' => 10,
        ]);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we create a start instance of the same challenge.
        $mi1 = $gudg->create_persisted_mission_instance($cha1, [
            'subjectid' => 2,
            'state' => mission_instance::STATE_STARTED,
            'counter' => 10,
        ]);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(0, $objinst->get_counter());

        // Then we mark the instance as completed.
        $mi1->set_state(mission_instance::STATE_COMPLETED);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(1, $objinst->get_counter());

        // Then we mark the instance as ended.
        $mi1->set_state(mission_instance::STATE_ENDED);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(2, $objinst->get_counter());

        // Then we create a new started instance, no change.
        $clock->bump(3600);
        $mi2 = $gudg->create_persisted_mission_instance($cha1, [
            'subjectid' => 2,
            'iteration' => 1,
            'state' => mission_instance::STATE_STARTED,
            'counter' => 10,
        ]);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(2, $objinst->get_counter());

        // Then we bump it completed.
        $mi2->set_state(mission_instance::STATE_COMPLETED);
        $type->initialise_state($objinst, $missioninst);
        $this->assertEquals(3, $objinst->get_counter());
    }

}
