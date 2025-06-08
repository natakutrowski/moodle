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

namespace block_gearup\exporter;

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

        $monday = get_string('monday', 'core_calendar');
        $tomorrow = get_string('tomorrow', 'core_calendar');
        $nextweek = get_string('nextweek', 'block_gearup');
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
     * @covers block_gearup\local\exporter\mission_instance_exporter
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

}
