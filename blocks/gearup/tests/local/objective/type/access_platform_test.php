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

use block_gearup\local\objective\type\access_platform;
use block_gearup\local\time\frequency_evaluator;
use block_gearup\tests\base_testcase;
use core_date;
use DateInterval;

/**
 * Testcase.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_gearup\local\objective\type\access_platform
 */
final class access_platform_test extends base_testcase {

    /** @var \block_gearup\local\mission\mission */
    protected $mission;
    /** @var \block_gearup\local\mission\mission_instance */
    protected $missioninst;

    public function setUp(): void {
        parent::setUp();
        $dg = $this->generator;
        $this->mission = $dg->mock_quest();
        $this->missioninst = $dg->mock_mission_instance($this->mission);
    }

    /**
     * Test.
     */
    public function test_mode_none(): void {
        $dg = $this->generator;
        $action = $dg->mock_action_loggedin(2);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_NONE], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => 2]);

        $this->assertTrue($type->is_action_compatible($action));
        $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst));
        $this->assertEquals(0, $inst->get_counter());
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(1, $inst->get_counter());
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(2, $inst->get_counter());
        $this->assertEquals(null, $inst->access_dormant_until());
        $this->assertEquals(null, $inst->access_stale_from());
    }

    /**
     * Test.
     */
    public function test_mode_day(): void {
        $dg = $this->generator;

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_DAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => 2]);

        $start = new \DateTimeImmutable('2021-07-01T08:00:00');
        $dataset = [
            [true, $dg->mock_action_loggedin(2, $start)],
            [false, $dg->mock_action_loggedin(2, $start->add(new DateInterval('PT1H')))], // Same day.
            [true, $dg->mock_action_loggedin(2, $start->add(new DateInterval('P1DT1H')))], // Next day, one hour later.
            [true, $dg->mock_action_loggedin(2, $start->add(new DateInterval('P5D')))], // Five days later.
            [false, $dg->mock_action_loggedin(2, $start->add(new DateInterval('P5DT1H')))], // Five days later, one hour later.
            [false, $dg->mock_action_loggedin(2, $start->add(new DateInterval('P5DT12H')))], // Five days later, 12 hours later.
        ];

        foreach ($dataset as $key => $entry) {
            $counter = $inst->get_counter();
            [$shouldincrement, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($shouldincrement) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            if ($shouldincrement) {
                $this->assertGreaterThan($counter, $inst->get_counter(), "Loop $key");
                $this->assertEquals($action->get_time()->add(new DateInterval('P1D'))->setTime(0, 0),
                    $inst->access_dormant_until(),
                    "Loop $key"
                );
                $this->assertEquals(null, $inst->access_stale_from(), "Loop $key");
            } else {
                $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            }
        }
    }

    /**
     * Test.
     */
    public function test_mode_day_with_timezone(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_DAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T23:59:59', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $this->assertEquals($timeforthem, $start);
        $this->assertNotEquals($timeforthem->format('d'), $start->format('d'));

        $dataset = [
            [true, $dg->mock_action_loggedin($user->id, $start)],
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT1H')))], // Next day for them.
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT1H')))], // Equivalent to two days later.
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT2H')))], // Still two days later.
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P4DT23H')))], // Five days later.
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5D')))], // Still five days later.
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT1H')))], // 6 days for them.
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT12H')))], // Still 6th day.
        ];

        foreach ($dataset as $key => $entry) {
            $counter = $inst->get_counter();
            [$shouldincrement, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($shouldincrement) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            if ($shouldincrement) {
                $this->assertGreaterThan($counter, $inst->get_counter(), "Loop $key");
                $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P1D'))->setTime(0, 0),
                    $inst->access_dormant_until(),
                    "Loop $key"
                );
                $this->assertEquals(null, $inst->access_stale_from(), "Loop $key");
            } else {
                $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            }
        }
    }

    /**
     * Test.
     */
    public function test_mode_week_with_timezone(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_WEEK], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        // This is a Thursday, so +4 days are needed to hit the next week.
        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T23:59:59', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $this->assertEquals($timeforthem, $start);
        $this->assertNotEquals($timeforthem->format('d'), $start->format('d'));

        $dataset = [
            [true, $dg->mock_action_loggedin($user->id, $start)],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT1H')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT1H')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT2H')))],
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P4DT23H')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5D')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT1H')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT12H')))],
            [true, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P10DT1M')))],
            [false, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P10DT1M1S')))],
        ];

        foreach ($dataset as $key => $entry) {
            $counter = $inst->get_counter();
            [$shouldincrement, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($shouldincrement) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            if ($shouldincrement) {
                $this->assertGreaterThan($counter, $inst->get_counter(), "Loop $key");
                $this->assertEquals($action->get_time()->setTimezone($usertz)->modify('monday next week')->setTime(0, 0),
                    $inst->access_dormant_until(),
                    "Loop $key"
                );
                $this->assertEquals(null, $inst->access_stale_from(), "Loop $key");
            } else {
                $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            }
        }
    }

    /**
     * Test.
     */
    public function test_mode_consec_day_with_timezone(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_CONSEC_DAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T23:59:59', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $this->assertEquals($timeforthem, $start);
        $this->assertNotEquals($timeforthem->format('d'), $start->format('d'));

        $dataset = [
            [true, 1, $dg->mock_action_loggedin($user->id, $start)],
            [true, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT1H')))],
            [true, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT1H')))],
            [false, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT2H')))],
            [true, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P4D')))],
            [true, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5D')))],
            [true, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT1H')))],
            [false, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P5DT10H')))],
            [true, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P10D')))],
        ];

        foreach ($dataset as $key => $entry) {
            [$ispassingconstraints, $counter, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($ispassingconstraints) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P1D'))->setTime(0, 0),
                $inst->access_dormant_until(),
                "Loop $key"
            );
            $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P2D'))->setTime(0, 0),
                $inst->access_stale_from(),
                "Loop $key"
            );
        }
    }

    /**
     * Test.
     */
    public function test_mode_consec_week_with_timezone(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_CONSEC_WEEK], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        // This is a Thursday, so 4 days are needed.
        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T23:59:59', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $this->assertEquals($timeforthem, $start);
        $this->assertNotEquals($timeforthem->format('d'), $start->format('d'));

        $dataset = [
            [true, 1, $dg->mock_action_loggedin($user->id, $start)],
            [false, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT1H')))],
            [false, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P3D')))],
            [true, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1W')))],
            [false, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P9D')))],
            [true, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P3W')))],
            [false, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P22D')))],
            [false, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P24D')))],
            [true, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P25D')))],
        ];

        foreach ($dataset as $key => $entry) {
            [$ispassingconstraints, $counter, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($ispassingconstraints) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            $this->assertEquals($action->get_time()->setTimezone($usertz)->modify('monday next week')->setTime(0, 0),
                $inst->access_dormant_until(),
                "Loop $key"
            );
            $this->assertEquals($action->get_time()->setTimezone($usertz)->modify('monday next week')->add(new DateInterval('P1W')),
                $inst->access_stale_from(),
                "Loop $key"
            );
        }
    }

    /**
     * Test.
     */
    public function test_mode_consec_weekday_with_timezone(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_CONSEC_WEEKDAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        // This is a Thursday.
        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T23:59:59', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $this->assertEquals($timeforthem, $start);
        $this->assertNotEquals($timeforthem->format('d'), $start->format('d'));

        $dataset = [
            [true, 1, $dg->mock_action_loggedin($user->id, $start)],
            [true, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT1H')))],
            [false, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('PT10H')))],
            [false, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P1DT1H')))], // Saturday.
            [false, 2, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P2DT1H')))], // Sunday.
            [true, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P3DT1H')))], // Monday.
            [false, 3, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P3DT3H')))], // Monday.
            [true, 4, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P4DT3H')))], // Tuesday.
            [true, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P7DT3H')))], // Friday.
            [true, 1, $dg->mock_action_loggedin($user->id, $start->add(new DateInterval('P11DT3H')))], // Tuesday.
        ];

        foreach ($dataset as $key => $entry) {
            [$ispassingconstraints, $counter, $action] = $entry;
            $this->assertTrue($type->is_action_compatible($action), "Loop $key");

            if ($ispassingconstraints) {
                $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            } else {
                $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst), "Loop $key");
            }

            $type->consume_action($action, $inst, $this->missioninst);
            $this->assertEquals($counter, $inst->get_counter(), "Loop $key");
            $this->assertEquals($action->get_time()->setTimezone($usertz)->modify('next weekday')->setTime(0, 0),
                $inst->access_dormant_until(),
                "Loop $key"
            );
            $this->assertEquals($action->get_time()->setTimezone($usertz)->modify('next weekday')->add(new DateInterval('P1D')),
                $inst->access_stale_from(),
                "Loop $key"
            );
        }
    }

    /**
     * Test.
     */
    public function test_mode_day_with_timezone_change_backwards(): void {
        global $DB;

        // What happens when the user changes their time zone between two calls?
        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'Australia/Perth']);
        $nexttz = 'America/New_York';

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_DAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T01:00:00', $usertz);

        $action = $dg->mock_action_loggedin($user->id, $timeforthem);
        $this->assertTrue($type->is_action_compatible($action));
        $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst));
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(1, $inst->get_counter());
        $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P1D'))->setTime(0, 0),
            $inst->access_dormant_until()
        );
        $this->assertEquals(null, $inst->access_stale_from());

        // Simulate the user reverting their timezone in their profile.
        $user->timezone = $nexttz;
        $DB->update_record('user', $user);
        $timezone = core_date::get_user_timezone_object($nexttz);
        $nexttimeforthem = $timeforthem->modify('next minute')->setTimezone($timezone);
        $this->assertNotEquals($nexttimeforthem->format('d'), $timeforthem->format('d'));

        $action = $dg->mock_action_loggedin($user->id, $nexttimeforthem);
        $prevdormantuntil = $inst->access_dormant_until();
        $this->assertTrue($type->is_action_compatible($action));
        $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst));
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(1, $inst->get_counter());
        $this->assertEquals($prevdormantuntil, $inst->access_dormant_until());
        $this->assertEquals(null, $inst->access_stale_from());
    }

    /**
     * Test.
     */
    public function test_mode_day_with_timezone_change_forward(): void {
        global $DB;

        // What happens when the user changes their time zone between two calls?
        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);
        $nexttz = 'Australia/Perth';

        $type = new access_platform();
        $obj = $dg->mock_objective($type, ['mode' => frequency_evaluator::MODE_DAY], ['countneeded' => 10]);
        $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);

        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T15:00:00', $usertz);

        $action = $dg->mock_action_loggedin($user->id, $timeforthem);
        $this->assertTrue($type->is_action_compatible($action));
        $this->assertTrue($type->is_action_passing_constraints($action, $inst, $this->missioninst));
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(1, $inst->get_counter());
        $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P1D'))->setTime(0, 0),
            $inst->access_dormant_until()
        );
        $this->assertEquals(null, $inst->access_stale_from());

        // Simulate the user advancing their timezone in their profile.
        $user->timezone = $nexttz;
        $DB->update_record('user', $user);
        $timezone = core_date::get_user_timezone_object($nexttz);
        $nexttimeforthem = $timeforthem->modify('next minute')->setTimezone($timezone);
        $this->assertNotEquals($nexttimeforthem->format('d'), $timeforthem->format('d'));

        $action = $dg->mock_action_loggedin($user->id, $nexttimeforthem);
        $this->assertTrue($type->is_action_compatible($action));
        $this->assertFalse($type->is_action_passing_constraints($action, $inst, $this->missioninst));
        $type->consume_action($action, $inst, $this->missioninst);
        $this->assertEquals(1, $inst->get_counter());
        $this->assertEquals($action->get_time()->setTimezone($usertz)->add(new DateInterval('P1D'))->setTime(0, 0),
            $inst->access_dormant_until()
        );
        $this->assertEquals(null, $inst->access_stale_from());
    }

    /**
     * Test.
     */
    public function test_reevaluate_state_basic(): void {
        global $CFG;

        $dg = $this->generator;
        $user = $this->getDataGenerator()->create_user(['timezone' => 'America/New_York']);
        $type = new access_platform();

        // This is a Thursday.
        $usertz = core_date::get_user_timezone_object($user);
        $timeforthem = new \DateTimeImmutable('2021-07-01T20:00:00', $usertz);
        $start = $timeforthem->setTimezone(core_date::get_user_timezone_object($CFG->timezone));
        $modes = [
            frequency_evaluator::MODE_CONSEC_DAY,
            frequency_evaluator::MODE_CONSEC_WEEK,
            frequency_evaluator::MODE_CONSEC_WEEKDAY,
        ];

        // For each of these modes, we trigger a match a while ago and re-evaluate today. They should
        // all have a state date that is before today, and thus should all reset the instance.
        foreach ($modes as $mode) {
            $obj = $dg->mock_objective($type, ['mode' => $mode]);
            $inst = $dg->mock_objective_instance($obj, ['subjectid' => $user->id]);
            $action = $dg->mock_action_loggedin($user->id, $start);
            $type->consume_action($action, $inst, $this->missioninst);
            $this->assertEquals(1, $inst->get_counter());
            $this->assertNotEquals(null, $inst->access_dormant_until());
            $this->assertNotEquals(null, $inst->access_stale_from());
            $type->reevaluate_state($inst);
            $this->assertEquals(0, $inst->get_counter());
            $this->assertEquals(null, $inst->access_dormant_until());
            $this->assertEquals(null, $inst->access_stale_from());
        }
    }
}
