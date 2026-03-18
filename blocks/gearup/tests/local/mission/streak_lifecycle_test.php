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
use block_gearup\local\model\mission_inst;
use block_gearup\local\utils\time_utils;
use block_gearup\tests\base_testcase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class streak_lifecycle_test extends base_testcase {

    /**
     * Test lifecycle.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_lifecycle(): void {
        global $DB;

        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => DAYSECS]);

        $mo = di::get('mission_operator');
        $oo = di::get('objective_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // Starts automatically.
        $expecteddeadline = $clock->now()->setTime(23, 59, 59);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame($mission->get_id(), $mi->get_mission()->get_id());
        $this->assertSame((int) $u1->id, $mi->get_subject_id());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
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
        $this->assertSame(1, $mi->get_counter());
        $this->assertSame($origtime, $mi->get_time_assigned()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_started()->getTimestamp()); // Set when streak hits 1.
        $this->assertSame($clock->time(), $mi->get_time_completed()->getTimestamp());
        $this->assertSame(false, $mi->needs_attention());
        $origstart = $mi->get_time_started();

        // Jump past the deadline.
        $clock->bump(($expecteddeadline->getTimestamp() - $clock->time()) + 1);
        $nextexpecteddeadline = $expecteddeadline->modify("+1 day");
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(1, $mi->get_counter());
        $this->assertSame($nextexpecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
        $this->assertSame(false, $mi->needs_attention());
        foreach ($mi->get_objective_instances() as $objinst) {
            $this->assertFalse($objinst->is_completed());
        }

        // Complete the objectives, once more.
        foreach ($mi->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi->get_state());
        $this->assertSame(2, $mi->get_counter());
        $this->assertSame($origstart->getTimestamp(), $mi->get_time_started()->getTimestamp()); // No change on counter 2.

        // Jump past the deadline, again
        $clock->bump(($nextexpecteddeadline->getTimestamp() - $clock->time()) + 1);
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(2, $mi->get_counter());
        foreach ($mi->get_objective_instances() as $objinst) {
            $this->assertFalse($objinst->is_completed());
        }

        // Now lose the streak.
        $clock->bump(($mi->get_deadline()->getTimestamp() - $clock->time()) + 1);
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(2, $mi->get_counter());
        $this->assertSame($clock->time(), $mi->get_time_completed()->getTimestamp());
        $this->assertSame($clock->time(), $mi->get_time_ended()->getTimestamp());

        // Confirm there is a new iteration.
        $this->assertEquals(2, mission_inst::count_records(['missionid' => $mission->get_id()]));
        $mi2 = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE,
            'id',
            ['iteration' => 1, 'missionid' => $mission->get_id()]
        ));

        $expecteddeadline = $mi->get_deadline()->modify("+1 day");
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertSame($mission->get_id(), $mi2->get_mission()->get_id());
        $this->assertSame((int) $u1->id, $mi2->get_subject_id());
        $this->assertSame(0.0, $mi2->get_completion_ratio());
        $this->assertSame(1, $mi2->get_iteration_number());
        $this->assertSame(0, $mi2->get_counter());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi2->get_deadline()->getTimestamp());
        $this->assertSame($clock->time(), $mi2->get_time_assigned()->getTimestamp());
        $this->assertSame($clock->time(), $mi2->get_time_started()->getTimestamp());
        $this->assertSame(false, $mi2->needs_attention());
    }

    /**
     * Test long overdue behaviour.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_long_overdue(): void {
        global $DB;

        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => DAYSECS]);

        $oo = di::get('objective_operator');
        $mo = di::get('mission_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // Starts automatically.
        $expecteddeadline = $clock->now()->setTime(23, 59, 59);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());

        $clock->bump(60);

        // Complete the objectives.
        foreach ($mi->get_objective_instances() as $objinst) {
            $oo->increment_instance_counter($objinst, 1);
            $this->assertTrue($objinst->is_completed());
        }
        $mo->evaluate_instance($mi);

        // Was marked as completed.
        $this->assertSame(mission_instance::STATE_COMPLETED, $mi->get_state());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(1, $mi->get_counter());

        // Jump way past into the future.
        $clock->bump(DAYSECS * 45);
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_ENDED, $mi->get_state());
        $this->assertSame(1.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(1, $mi->get_counter());
        $this->assertSame($clock->time(), $mi->get_time_ended()->getTimestamp());

        // Confirm there is a new iteration.
        $this->assertEquals(2, mission_inst::count_records(['missionid' => $mission->get_id()]));
        $mi2 = di::get('repository')->get_instance($DB->get_field(mission_inst::TABLE,
            'id',
            ['iteration' => 1, 'missionid' => $mission->get_id()]
        ));

        // New deadline is relative to now.
        $expecteddeadline = $clock->now()->setTime(23, 59, 59);
        $this->assertSame(mission_instance::STATE_STARTED, $mi2->get_state());
        $this->assertSame(0.0, $mi2->get_completion_ratio());
        $this->assertSame(1, $mi2->get_iteration_number());
        $this->assertSame(0, $mi2->get_counter());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi2->get_deadline()->getTimestamp());
        $this->assertSame($clock->time(), $mi2->get_time_assigned()->getTimestamp());
        $this->assertSame($clock->time(), $mi2->get_time_started()->getTimestamp());
    }

    /**
     * Test that zero instances restart.
     *
     * @covers \block_gearup\local\mission\operator
     */
    public function test_zero_instance_restart(): void {
        global $DB;

        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => DAYSECS, 'objectives' => [
            ['type' => 'manual', 'countneeded' => 10],
            ['type' => 'manual', 'countneeded' => 1],
        ]]);
        $obj0id = $mission->get_objectives()[0]->get_id();
        $obj1id = $mission->get_objectives()[1]->get_id();

        $oo = di::get('objective_operator');
        $mo = di::get('mission_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        // Starts automatically.
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());

        $oo->increment_instance_counter($mi->get_instance_of_objective($obj0id), 5);
        $oo->increment_instance_counter($mi->get_instance_of_objective($obj1id), 1);
        $mo->evaluate_instance($mi);

        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0.75, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame(5, $mi->get_instance_of_objective($obj0id)->get_counter());
        $this->assertFalse($mi->get_instance_of_objective($obj0id)->is_completed());
        $this->assertSame(1, $mi->get_instance_of_objective($obj1id)->get_counter());
        $this->assertTrue($mi->get_instance_of_objective($obj1id)->is_completed());
        $origstart = $mi->get_time_started();

        // We will lose the streak.
        $clock->bump($mi->get_deadline()->getTimestamp() - $clock->time() + 1);
        $expecteddeadline = $mi->get_deadline()->modify("+1 day");
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame($origstart->getTimestamp(), $mi->get_time_started()->getTimestamp());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
        $this->assertSame(0, $mi->get_instance_of_objective($obj0id)->get_counter());
        $this->assertFalse($mi->get_instance_of_objective($obj0id)->is_completed());
        $this->assertSame(0, $mi->get_instance_of_objective($obj1id)->get_counter());
        $this->assertFalse($mi->get_instance_of_objective($obj1id)->is_completed());

        // Confirm there is no new iteration.
        $this->assertEquals(1, mission_inst::count_records(['missionid' => $mission->get_id()]));

        // We go way past the deadline once more.
        $clock->bump($mi->get_deadline()->getTimestamp() - $clock->time() + DAYSECS * 3);
        $expecteddeadline = $clock->now()->setTime(23, 59, 59);
        if ($expecteddeadline < $clock->now()) {
            $expecteddeadline = $expecteddeadline->modify("+1 day");
        }
        $mo->evaluate_instance($mi);
        $this->assertSame(mission_instance::STATE_STARTED, $mi->get_state());
        $this->assertSame(0.0, $mi->get_completion_ratio());
        $this->assertSame(0, $mi->get_iteration_number());
        $this->assertSame(0, $mi->get_counter());
        $this->assertSame($origstart->getTimestamp(), $mi->get_time_started()->getTimestamp());
        $this->assertSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
        $this->assertSame(0, $mi->get_instance_of_objective($obj0id)->get_counter());
        $this->assertFalse($mi->get_instance_of_objective($obj0id)->is_completed());
        $this->assertSame(0, $mi->get_instance_of_objective($obj1id)->get_counter());
        $this->assertFalse($mi->get_instance_of_objective($obj1id)->is_completed());

        // Confirm there is no new iteration.
        $this->assertEquals(1, mission_inst::count_records(['missionid' => $mission->get_id()]));
    }

    /**
     * Test restart with objectives changed.
     *
     * @covers \block_gearup\local\mission\operator
     * @covers \block_gearup\local\objective\operator
     */
    public function test_restart_with_objectives_changed(): void {
        $clock = $this->get_frozen_clock();
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => DAYSECS, 'objectives' => [
            ['type' => 'manual', 'countneeded' => 10],
            ['type' => 'manual', 'countneeded' => 1],
        ]]);
        $obj0id = $mission->get_objectives()[0]->get_id();
        $obj1id = $mission->get_objectives()[1]->get_id();

        $oo = di::get('objective_operator');
        $mo = di::get('mission_operator');

        $mi = $mo->assign_mission($mission, $u1->id);
        $oo->increment_instance_counter($mi->get_instance_of_objective($obj0id), 5);
        $oo->increment_instance_counter($mi->get_instance_of_objective($obj1id), 1);
        $mo->evaluate_instance($mi);

        $this->assertEquals(0.75, $mi->get_completion_ratio());

        // Mess with the objectives.
        $mission->get_objective($obj0id)->get_persistent()->delete();
        $obj2id = $gudg->create_objective_model([
            'missionid' => $mission->get_id(),
            'type' => 'manual',
            'countneeded' => 2,
        ])->get('id');

        // Refresh the data.
        $mission = di::get('repository')->get_mission($mission->get_id());
        $mi = di::get('repository')->get_instance($mi->get_id());

        // Force a restart.
        $clock->bump(DAYSECS * 10);
        $mo->evaluate_instance($mi);

        // Validate the objective update.
        $objidsininst = array_map(function ($objinst) {
            return $objinst->get_objective()->get_id();
        }, $mi->get_objective_instances());
        $this->assertCount(2, $objidsininst);
        $this->assertNotContains($obj0id, $objidsininst);
        $this->assertContains($obj1id, $objidsininst);
        $this->assertContains($obj2id, $objidsininst);
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function deadline_calculation_provider(): Generator {
        $tuesday = new \DateTimeImmutable('2025-07-01 12:00:00', new DateTimeZone('Australia/Perth'));
        $friday = new \DateTimeImmutable('2025-07-04 12:00:00', new DateTimeZone('Australia/Perth'));
        $saturday = new \DateTimeImmutable('2025-07-05 12:00:00', new DateTimeZone('Australia/Perth'));
        $sunday = new \DateTimeImmutable('2025-07-06 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp1 = new \DateTimeImmutable('2025-07-13 12:00:00', new DateTimeZone('Australia/Perth'));

        $lastdayofjuly = new \DateTimeImmutable('2025-07-31 12:00:00', new DateTimeZone('Australia/Perth'));

        // Daily.
        yield [DAYSECS, $tuesday, $tuesday->setTime(23, 59, 59)];

        // Weekly.
        yield [WEEKSECS, $tuesday, $sunday->setTime(23, 59, 59)];
        yield [WEEKSECS, $sunday, $sunday->setTime(23, 59, 59)];

        // Fortnightly.
        yield [WEEKSECS * 2, $tuesday, $sundayp1->setTime(23, 59, 59)];
        yield [WEEKSECS * 2, $friday, $sundayp1->setTime(23, 59, 59)];
        yield [WEEKSECS * 2, $saturday, $sundayp1->setTime(23, 59, 59)];
        yield [WEEKSECS * 2, $sunday, $sundayp1->setTime(23, 59, 59)];

        // Monthly.
        yield [DAYSECS * 30, $tuesday, $lastdayofjuly->setTime(23, 59, 59)];
        yield [DAYSECS * 30, $friday, $lastdayofjuly->setTime(23, 59, 59)];
        yield [DAYSECS * 30, $lastdayofjuly, $lastdayofjuly->setTime(23, 59, 59)];

        // Daily on week days.
        yield [time_utils::DAILY_WEEKDAY, $tuesday, $tuesday->setTime(23, 59, 59)];
        yield [time_utils::DAILY_WEEKDAY, $friday, $friday->setTime(23, 59, 59)];
        yield [time_utils::DAILY_WEEKDAY, $saturday, $saturday->modify('next Monday')->setTime(23, 59, 59)];
    }

    /**
     * Test deadlines.
     *
     * @param int $timelimit The time limit in seconds.
     * @param DateTimeImmutable $timenow The current time.
     * @param DateTimeImmutable $expecteddeadline The expected deadline.
     * @covers \block_gearup\local\mission\operator
     * @dataProvider deadline_calculation_provider
     */
    public function test_deadline_calculation(
        int $timelimit,
        DateTimeImmutable $timenow,
        DateTimeImmutable $expecteddeadline
    ): void {

        $this->get_frozen_clock($timenow->getTimestamp());
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => $timelimit]);

        $mo = di::get('mission_operator');
        $mi = $mo->assign_mission($mission, $u1->id);

        $this->assertSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function next_deadline_calculation_provider(): Generator {
        $tuesday = new \DateTimeImmutable('2025-07-01 12:00:00', new DateTimeZone('Australia/Perth'));
        $friday = new \DateTimeImmutable('2025-07-04 12:00:00', new DateTimeZone('Australia/Perth'));
        $saturday = new \DateTimeImmutable('2025-07-05 12:00:00', new DateTimeZone('Australia/Perth'));
        $sunday = new \DateTimeImmutable('2025-07-06 12:00:00', new DateTimeZone('Australia/Perth'));
        $tuesdayp1 = new \DateTimeImmutable('2025-07-08 12:00:00', new DateTimeZone('Australia/Perth'));
        $fridayp1 = new \DateTimeImmutable('2025-07-11 12:00:00', new DateTimeZone('Australia/Perth'));
        $saturdayp1 = new \DateTimeImmutable('2025-07-12 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp1 = new \DateTimeImmutable('2025-07-13 12:00:00', new DateTimeZone('Australia/Perth'));
        $tuesdayp2 = new \DateTimeImmutable('2025-07-15 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp2 = new \DateTimeImmutable('2025-07-20 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp3 = new \DateTimeImmutable('2025-07-27 12:00:00', new DateTimeZone('Australia/Perth'));

        $lastdayofjuly = new \DateTimeImmutable('2025-07-31 12:00:00', new DateTimeZone('Australia/Perth'));
        $lastdayofaugust = new \DateTimeImmutable('2025-08-31 12:00:00', new DateTimeZone('Australia/Perth'));

        // Daily.
        yield [DAYSECS, $tuesday, $friday, $friday->setTime(23, 59, 59)];

        // Weekly.
        yield [WEEKSECS, $tuesday, $tuesdayp1, $sundayp1->setTime(23, 59, 59)];
        yield [WEEKSECS, $sunday, $sundayp1, $sundayp1->setTime(23, 59, 59)];

        // Fortnightly.
        yield [WEEKSECS * 2, $tuesday, $tuesdayp2, $sundayp3->setTime(23, 59, 59)];
        yield [WEEKSECS * 2, $tuesday, $sundayp2, $sundayp3->setTime(23, 59, 59)];
        yield [WEEKSECS * 2, $tuesday, $sundayp3, $sundayp3->setTime(23, 59, 59)];

        // Monthly.
        yield [DAYSECS * 30, $tuesday, $tuesday->modify("+35 days"), $lastdayofaugust->setTime(23, 59, 59)];
        yield [DAYSECS * 30, $friday, $friday->modify("+35 days"), $lastdayofaugust->setTime(23, 59, 59)];
        yield [DAYSECS * 30, $lastdayofjuly, $lastdayofaugust, $lastdayofaugust->setTime(23, 59, 59)];

        // Daily on week days.
        yield [time_utils::DAILY_WEEKDAY, $tuesday, $tuesdayp1, $tuesdayp1->setTime(23, 59, 59)];
        yield [time_utils::DAILY_WEEKDAY, $friday, $fridayp1, $fridayp1->setTime(23, 59, 59)];
        yield [time_utils::DAILY_WEEKDAY, $saturday, $saturdayp1, $saturdayp1->modify('next Monday')->setTime(23, 59, 59)];
    }

    /**
     * Test deadlines.
     *
     * @param int $timelimit The time limit in seconds.
     * @param DateTimeImmutable $timenow The current time
     * @param DateTimeImmutable $timereeval The time at which we reeval the instance.
     * @param DateTimeImmutable $expecteddeadline The expected deadline.
     * @covers \block_gearup\local\mission\operator
     * @dataProvider next_deadline_calculation_provider
     */
    public function test_next_deadline_calculation(
        int $timelimit,
        DateTimeImmutable $timenow,
        DateTimeImmutable $timereeval,
        DateTimeImmutable $expecteddeadline
    ): void {

        $clock = $this->get_frozen_clock($timenow->getTimestamp());
        $dg = $this->getDataGenerator();
        $gudg = $this->generator;

        $u1 = $dg->create_user();
        $mission = $gudg->create_streak(['timelimit' => $timelimit]);

        $mo = di::get('mission_operator');
        $mi = $mo->assign_mission($mission, $u1->id);
        $this->assertNotSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());

        $clock->set_to($timereeval->getTimestamp());
        $mo->evaluate_instance($mi);
        $this->assertSame($expecteddeadline->getTimestamp(), $mi->get_deadline()->getTimestamp());
    }

}
