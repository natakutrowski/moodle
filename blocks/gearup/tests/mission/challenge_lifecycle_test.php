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

namespace block_gearup\mission;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\model\mission_inst;
use block_gearup\tests\base_testcase;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class challenge_lifecycle_test extends base_testcase {

    /**
     * Test lifecycle.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_one_off_no_limit_lifecycle(): void {
        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_challenge([
            'timelimit' => 0,
            'repeatcount' => mission::REPEAT_NEVER,
        ]);

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // challenge starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame($mission->get_id(), $mi->get_mission()->get_id());
        $this->assertSame((int) $u1->id, $mi->get_subject_id());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame(null, $mi->get_deadline());
        $this->assertSame($clock->time(), $mi->get_time_assigned()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_started()->getTimestamp());
        $this->assertSame(false, $mi->needs_attention());

        $origtime = $clock->time();
        $clock->bump(60);

        // Complete the objectives.
        foreach ($mi->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi);

        // Was marked as completed.
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi->get_state());
        $this->assertSame(1.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame($origtime, $mi->get_time_assigned()->getTimestamp());
        $this->assertSame($origtime, $mi->get_time_started()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_completed()->getTimestamp());
        $this->assertSame(false, $mi->needs_attention());

        // We keep the challenge alive for a day.
        $clock->bump(86400);
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi->get_state());

        // The challenge ends.
        $clock->bump(1);
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());

        // Confirm there is no iteration.
        $this->assertEquals(1, mission_inst::count_records(['missionid' => $mission->get_id()]));
    }

    /**
     * Test lifecycle.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_one_off_limit_lifecycle(): void {
        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_challenge([
            'timelimit' => DAYSECS,
            'repeatcount' => mission::REPEAT_NEVER,
        ]);

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');

        $mi1 = $mo->assign_mission($mission, $u1->id);
        $mi2 = $mo->assign_mission($mission, $u2->id);
        $expecteddeadline = $clock->now()->modify("+86400 seconds");

        // challenge starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi1->get_state());
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertEquals($expecteddeadline->getTimestamp(), $mi1->get_deadline()->getTimestamp());
        $this->assertEquals($expecteddeadline->getTimestamp(), $mi2->get_deadline()->getTimestamp());

        $clock->bump(60);

        // Complete the objectives.
        foreach ($mi1->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi1);
        $mo->evaluate_instance($mi2);

        // Was marked as completed.
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi1->get_state());
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertSame(1.0, $mi1->get_completion_ratio());
        $this->assertSame(0.0, $mi2->get_completion_ratio());

        // Advanced past both deadlines.
        $clock->bump(($expecteddeadline->getTimestamp() - $clock->time()) + 1);

        $mo->evaluate_instance($mi1);
        $this->assertSame(mission_instance::STATE_ENDED, $mi1->get_state());
        $this->assertSame(1.0, $mi1->get_completion_ratio());

        // Challenge failed.
        $mo->evaluate_instance($mi2);
        $this->assertSame(mission_instance::STATE_ENDED, $mi2->get_state());
        $this->assertSame(0.0, $mi2->get_completion_ratio());

        // Confirm there is no iteration.
        $this->assertEquals(1, mission_inst::count_records(['missionid' => $mission->get_id(), 'subjectid' => $u1->id]));
        $this->assertEquals(1, mission_inst::count_records(['missionid' => $mission->get_id(), 'subjectid' => $u2->id]));
    }

    /**
     * Test lifecycle.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_repeating_lifecycle(): void {
        global $DB;

        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        $mission = $gudg->create_challenge([
            'timelimit' => DAYSECS,
            'repeatcount' => mission::REPEAT_ALWAYS,
        ]);

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');

        $mi1 = $mo->assign_mission($mission, $u1->id);
        $mi2 = $mo->assign_mission($mission, $u2->id);
        $expecteddeadline = $clock->now()->setTime(23, 59, 59);

        // challenge starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi1->get_state());
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi1->get_deadline()->getTimestamp());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi2->get_deadline()->getTimestamp());

        $clock->bump(60);

        // Complete the objectives.
        foreach ($mi1->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi1);
        $mo->evaluate_instance($mi2);

        // Was marked as completed.
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi1->get_state());
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertSame(1.0, $mi1->get_completion_ratio());
        $this->assertSame(0.0, $mi2->get_completion_ratio());

        // Advanced past both deadlines.
        $clock->bump(($expecteddeadline->getTimestamp() - $clock->time()) + 1);

        $mo->evaluate_instance($mi1);
        $this->assertSame(mission_instance::STATE_ENDED, $mi1->get_state());
        $this->assertSame(0, $mi1->get_iteration_number());
        $this->assertSame(1.0, $mi1->get_completion_ratio());

        // Challenge failed.
        $mo->evaluate_instance($mi2);
        $this->assertSame(mission_instance::STATE_ENDED, $mi2->get_state());
        $this->assertSame(0, $mi2->get_iteration_number());
        $this->assertSame(0.0, $mi2->get_completion_ratio());

        // Confirm that each were iterated.
        $this->assertEquals(2, mission_inst::count_records(['missionid' => $mission->get_id(), 'subjectid' => $u1->id]));
        $this->assertEquals(2, mission_inst::count_records(['missionid' => $mission->get_id(), 'subjectid' => $u2->id]));
        $mi1bid = $DB->get_field(mission_inst::TABLE, 'id', [
            'missionid' => $mission->get_id(),
            'subjectid' => $u1->id,
            'iteration' => 1,
        ]);
        $mi2bid = $DB->get_field(mission_inst::TABLE, 'id', [
            'missionid' => $mission->get_id(),
            'subjectid' => $u2->id,
            'iteration' => 1,
        ]);

        $mi1b = di::get('repository')->get_instance($mi1bid);
        $mi2b = di::get('repository')->get_instance($mi2bid);

        // Confirm iterated state.
        $this->assertSame((int) $u1->id, $mi1b->get_subject_id());
        $this->assertSame((int) $u2->id, $mi2b->get_subject_id());
        $expectednextdeadline = $expecteddeadline->modify("next day")->setTime(23, 59, 59);
        foreach ([$mi1b, $mi2b] as $mi) {
            $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
            $this->assertSame($expectednextdeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
            $this->assertSame(1, $mi->get_iteration_number());
            $this->assertSame(0.0, $mi->get_completion_ratio());
            $this->assertSame($clock->now()->getTimestamp(), $mi->get_time_assigned()->getTimestamp());
            $this->assertSame($clock->now()->getTimestamp(), $mi->get_time_started()->getTimestamp());
        }
    }

    /**
     * Test deadlines.
     *
     * @covers \block_gearup\local\mission\operator
     */
    public function test_deadlines(): void {
        global $DB;

        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();

        $mday = $gudg->create_challenge(['timelimit' => DAYSECS, 'repeatcount' => mission::REPEAT_ALWAYS]);
        $mweek = $gudg->create_challenge(['timelimit' => WEEKSECS, 'repeatcount' => mission::REPEAT_ALWAYS]);
        $m2week = $gudg->create_challenge(['timelimit' => WEEKSECS * 2, 'repeatcount' => mission::REPEAT_ALWAYS]);
        $mmonth = $gudg->create_challenge(['timelimit' => DAYSECS * 30, 'repeatcount' => mission::REPEAT_ALWAYS]);
        $mdaynorepeat = $gudg->create_challenge(['timelimit' => DAYSECS, 'repeatcount' => mission::REPEAT_NEVER]);
        $mweeknorepeat = $gudg->create_challenge(['timelimit' => WEEKSECS, 'repeatcount' => mission::REPEAT_NEVER]);
        $m2weeknorepeat = $gudg->create_challenge(['timelimit' => WEEKSECS * 2, 'repeatcount' => mission::REPEAT_NEVER]);
        $mmonthnorepeat = $gudg->create_challenge(['timelimit' => DAYSECS * 30, 'repeatcount' => mission::REPEAT_NEVER]);

        $mo = di::get('mission_operator');

        $miday = $mo->assign_mission($mday, $u1->id);
        $miweek = $mo->assign_mission($mweek, $u1->id);
        $mi2week = $mo->assign_mission($m2week, $u1->id);
        $mimonth = $mo->assign_mission($mmonth, $u1->id);
        $midaynorepeat = $mo->assign_mission($mdaynorepeat, $u1->id);
        $miweeknorepeat = $mo->assign_mission($mweeknorepeat, $u1->id);
        $mi2weeknorepeat = $mo->assign_mission($m2weeknorepeat, $u1->id);
        $mimonthnorepeat = $mo->assign_mission($mmonthnorepeat, $u1->id);

        $expecteddaydeadline = $clock->now()->setTime(23, 59, 59);
        $expectedweekdeadline = $clock->now()->modify("this Sunday")->setTime(23, 59, 59);
        $expected2weekdeadline = $clock->now()->modify("this Sunday")->modify("next Sunday")->setTime(23, 59, 59);
        $expectedmonthdeadline = $clock->now()->modify("last day of this month")->setTime(23, 59, 59);
        $expecteddaydeadlinenorepeat = $clock->now()->modify("+86400 seconds");
        $expectedweekdeadlinenorepeat = $clock->now()->modify("+" . WEEKSECS . " seconds");
        $expected2weekdeadlinenorepeat = $clock->now()->modify("+" . WEEKSECS * 2 . " seconds");
        $expectedmonthdeadlinenorepeat = $clock->now()->modify("+1 month");

        $this->assertSame($expecteddaydeadline->getTimestamp(), $miday->get_deadline()->getTimestamp());
        $this->assertSame($expectedweekdeadline->getTimestamp(), $miweek->get_deadline()->getTimestamp());
        $this->assertSame($expected2weekdeadline->getTimestamp(), $mi2week->get_deadline()->getTimestamp());
        $this->assertSame($expectedmonthdeadline->getTimestamp(), $mimonth->get_deadline()->getTimestamp());
        $this->assertSame($expecteddaydeadlinenorepeat->getTimestamp(), $midaynorepeat->get_deadline()->getTimestamp());
        $this->assertSame($expectedweekdeadlinenorepeat->getTimestamp(), $miweeknorepeat->get_deadline()->getTimestamp());
        $this->assertSame($expected2weekdeadlinenorepeat->getTimestamp(), $mi2weeknorepeat->get_deadline()->getTimestamp());
        $this->assertSame($expectedmonthdeadlinenorepeat->getTimestamp(), $mimonthnorepeat->get_deadline()->getTimestamp());

        $clock->bump(31 * DAYSECS);

        $mo->evaluate_instance($miday);
        $mo->evaluate_instance($miweek);
        $mo->evaluate_instance($mi2week);
        $mo->evaluate_instance($mimonth);

        $miday = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE, 'id',
            ['iteration' => 1, 'missionid' => $mday->get_id()]));
        $miweek = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE, 'id',
            ['iteration' => 1, 'missionid' => $mweek->get_id()]));
        $mi2week = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE, 'id',
            ['iteration' => 1, 'missionid' => $m2week->get_id()]));
        $mimonth = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE, 'id',
            ['iteration' => 1, 'missionid' => $mmonth->get_id()]));

        $expecteddaydeadline = $clock->now()->setTime(23, 59, 59);
        $expectedweekdeadline = $clock->now()->modify("this Sunday")->setTime(23, 59, 59);
        $expected2weekdeadline = $clock->now()->modify("this Sunday")->modify("next Sunday")->setTime(23, 59, 59);
        $expectedmonthdeadline = $clock->now()->modify("last day of this month")->setTime(23, 59, 59);
        $this->assertSame($expecteddaydeadline->getTimestamp(), $miday->get_deadline()->getTimestamp());
        $this->assertSame($expectedweekdeadline->getTimestamp(), $miweek->get_deadline()->getTimestamp());
        $this->assertSame($expected2weekdeadline->getTimestamp(), $mi2week->get_deadline()->getTimestamp());
        $this->assertSame($expectedmonthdeadline->getTimestamp(), $mimonth->get_deadline()->getTimestamp());
    }

}
