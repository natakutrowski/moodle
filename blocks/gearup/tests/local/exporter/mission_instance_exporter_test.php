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

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\local\exporter\mission_instance_exporter;
use block_gearup\local\factory\access_permissions_factory;
use block_gearup\local\mission\achievement;
use block_gearup\local\mission\challenge;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\quest;
use block_gearup\local\mission\streak;
use block_gearup\local\utils\time_utils;
use block_gearup\tests\base_testcase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;

/**
 * Tests for Level Up Quest
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mission_instance_exporter_test extends base_testcase {

    /**
     * Next start relative ref provider.
     *
     * @return array
     */
    public static function next_start_relative_ref_provider(): array {
        $mon = new DateTimeImmutable('next Monday');
        $tue = new DateTimeImmutable('next Tuesday');
        $wed = new DateTimeImmutable('next Wednesday');
        $thu = new DateTimeImmutable('next Thursday');
        $fri = new DateTimeImmutable('next Friday');
        $sat = new DateTimeImmutable('next Saturday');
        $sun = new DateTimeImmutable('next Sunday');

        $firstdayofmonth = new DateTimeImmutable('last day of this month');
        $lastdayofmonth = new DateTimeImmutable('first day of this month');

        $monday = get_string('monday', 'core_calendar');
        $tomorrow = get_string('tomorrow', 'core_calendar');
        $nextweek = get_string('nextweek', 'block_gearup');
        $nextmonth = get_string('nextmonth', 'block_gearup');
        return [
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $mon, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $tue, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $wed, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $thu, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $fri, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $sat, $tomorrow],
            [streak::class, DAYSECS, mission::REPEAT_ALWAYS, $sun, $tomorrow],

            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $mon, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $tue, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $wed, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $thu, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $fri, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $sat, $nextweek],
            [streak::class, WEEKSECS, mission::REPEAT_ALWAYS, $sun, $nextweek],

            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $mon, $tomorrow],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $tue, $tomorrow],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $wed, $tomorrow],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $thu, $tomorrow],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $fri, $monday],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $sat, $monday],
            [streak::class, time_utils::DAILY_WEEKDAY, mission::REPEAT_ALWAYS, $sun, $monday],

            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $mon, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $tue, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $wed, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $thu, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $fri, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $sat, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $sun, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $firstdayofmonth, $nextmonth],
            [streak::class, DAYSECS * 30, mission::REPEAT_ALWAYS, $lastdayofmonth, $nextmonth],

            // Currently not supported.
            [challenge::class, DAYSECS, mission::REPEAT_ALWAYS, $mon, null],
            [challenge::class, WEEKSECS, mission::REPEAT_NEVER, $mon, null],

            // Incompatible types.
            [quest::class, 0, mission::REPEAT_NEVER, $mon, null],
            [achievement::class, 0, mission::REPEAT_NEVER, $mon, null],
        ];
    }

    /**
     * Test next start relative ref.
     *
     * @dataProvider next_start_relative_ref_provider
     * @covers \block_gearup\local\exporter\mission_instance_exporter
     */
    public function test_next_start_relative_ref($type, $timelimit, $repeat, \DateTimeImmutable $deadline, $expected): void {
        global $PAGE;

        $gudg = $this->generator;
        $mission = $gudg->mock_mission([
            'type' => $type,
            'timelimit' => $timelimit,
            'repeat' => $repeat,
        ]);
        $mi = $gudg->mock_mission_instance($mission, [
            'subjectid' => 2,
            'deadline' => $deadline,
        ]);

        $exporter = di::get('exporter_factory')->get_mission_instance_exporter($mi, []);
        $data = $exporter->export($PAGE->get_renderer('block_gearup'));
        $this->assertSame($expected, $data->nextstartrelativeref);
    }

    /**
     * Provider.
     *
     * @return Generator
     */
    public static function fortnightly_streak_next_start_relative_ref_provider(): Generator {
        $nextweek = get_string('nextweek', 'block_gearup');
        $intwoweeks = get_string('intwoweeks', 'block_gearup');

        $tuesday = new \DateTimeImmutable('2025-07-01 12:00:00', new DateTimeZone('Australia/Perth'));
        $friday = new \DateTimeImmutable('2025-07-04 12:00:00', new DateTimeZone('Australia/Perth'));
        $sunday = new \DateTimeImmutable('2025-07-06 12:00:00', new DateTimeZone('Australia/Perth'));

        $sundayp1 = new \DateTimeImmutable('2025-07-13 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp2 = new \DateTimeImmutable('2025-07-20 12:00:00', new DateTimeZone('Australia/Perth'));
        $sundayp3 = new \DateTimeImmutable('2025-07-27 12:00:00', new DateTimeZone('Australia/Perth'));

        $sundec = new \DateTimeImmutable('2025-12-28 12:00:00', new DateTimeZone('Australia/Perth'));
        $mondec = new \DateTimeImmutable('2025-12-29 12:00:00', new DateTimeZone('Australia/Perth'));
        $sunjanp1 = new \DateTimeImmutable('2026-01-04 12:00:00', new DateTimeZone('Australia/Perth'));
        $sunp1janp1 = new \DateTimeImmutable('2026-01-11 12:00:00', new DateTimeZone('Australia/Perth'));

        yield [$tuesday, $sunday->setTime(23, 59, 59), $nextweek];
        yield [$friday, $sunday->setTime(23, 59, 59), $nextweek];
        yield [$sunday, $sunday->setTime(23, 59, 59), $nextweek];

        yield [$tuesday, $sundayp1->setTime(23, 59, 59), $intwoweeks];
        yield [$friday, $sundayp1->setTime(23, 59, 59), $intwoweeks];
        yield [$sunday, $sundayp1->setTime(23, 59, 59), $intwoweeks];

        // We don't distinguish between in two weeks, and in three weeks because that's unexpected.
        yield [$tuesday, $sundayp2->setTime(23, 59, 59), $intwoweeks];
        yield [$friday, $sundayp2->setTime(23, 59, 59), $intwoweeks];
        yield [$sunday, $sundayp2->setTime(23, 59, 59), $intwoweeks];

        yield [$sundayp1, $sundayp2->setTime(23, 59, 59), $intwoweeks];
        yield [$sundayp2, $sundayp3->setTime(23, 59, 59), $intwoweeks];
        yield [$sundayp3, $sundayp3->setTime(23, 59, 59), $nextweek];

        // Across years.
        yield [$mondec, $sunjanp1->setTime(23, 59, 59), $nextweek];
        yield [$sundec, $sunjanp1->setTime(23, 59, 59), $intwoweeks];
        yield [$sundec, $sunp1janp1->setTime(23, 59, 59), $intwoweeks];
    }

    /**
     * Test fortnightly streaks next start relative ref.
     *
     * @param \DateTimeImmutable $now The current time.
     * @param \DateTimeImmutable $deadline The deadline.
     * @param string $expected The expected relative reference.
     * @covers \block_gearup\local\exporter\mission_instance_exporter
     * @dataProvider fortnightly_streak_next_start_relative_ref_provider
     */
    public function test_fortnightly_streak_next_start_relative_ref(
        \DateTimeImmutable $now,
        \DateTimeImmutable $deadline,
        $expected
    ): void {
        global $PAGE;

        $this->get_frozen_clock($now->getTimestamp());
        $gudg = $this->generator;
        $mission = $gudg->mock_streak([
            'timelimit' => WEEKSECS * 2,
            'repeat' => mission::REPEAT_ALWAYS,
        ]);
        $mi = $gudg->mock_mission_instance($mission, [
            'subjectid' => 2,
            'deadline' => $deadline,
        ]);

        $exporter = di::get('exporter_factory')->get_mission_instance_exporter($mi, []);
        $data = $exporter->export($PAGE->get_renderer('block_gearup'));
        $this->assertSame($expected, $data->nextstartrelativeref);
    }

}
