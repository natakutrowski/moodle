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

namespace block_gearup\local\mission;

use block_gearup\di;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\utils\json_utils;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class achievement_lifecycle_test extends base_testcase {

    /**
     * Test achievement lifecycle.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_achievement_lifecycle(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_achievement();

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // Achievement starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame($mission->get_id(), $mi->get_mission()->get_id());
        $this->assertSame((int) $u1->id, $mi->get_subject_id());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame(null, $mi->get_deadline());
        $this->assertSame(false, $mi->needs_attention());

        // Nothing in announce list.
        $this->assertEmpty(json_utils::decode_to_list(get_user_preferences('block_gearup_achievements_ctxids', '', $u1->id)));

        // Complete the objectives.
        foreach ($mi->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi);

        // Achievement was automatically ended.
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame(1.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame(true, $mi->needs_attention());

        // Confirm record of context IDs where to announce.
        $this->assertEquals([SYSCONTEXTID], json_utils::decode_to_list(
            get_user_preferences('block_gearup_achievements_ctxids', '', $u1->id)
        ));
    }

    /**
     * Test achievement lifecycle clock.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_achievement_lifecycle_clock(): void {
        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_achievement();

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // Achievement starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame($clock->time(), $mi->get_time_assigned()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_started()->getTimestamp());

        $origtime = $clock->time();
        $clock->bump(60);

        // Complete the objectives.
        foreach ($mi->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi);

        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame($origtime, $mi->get_time_assigned()->getTimestamp());
        $this->assertSame($origtime, $mi->get_time_started()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_completed()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_ended()->getTimestamp());
    }

    /**
     * Test forced completed.
     *
     * @covers \block_gearup\local\mission\operator
     */
    public function test_achievement_force_completed(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_achievement();

        $mo = di::get('mission_operator');

        $mi = $mo->assign_mission($mission, $u1->id);
        $mo->complete_instance($mi);
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(true, $mi->needs_attention());
    }

    /**
     * Test premature ending.
     *
     * @covers \block_gearup\local\mission\operator
     */
    public function test_achievement_premature_end(): void {
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_achievement();

        $mo = di::get('mission_operator');

        $mi = $mo->assign_mission($mission, $u1->id);
        $mo->end_instance($mi);
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(true, $mi->needs_attention());
    }

}
