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
// See <https://levelup.plus>.

namespace block_gearup\local\exporter;

use block_gearup\di;
use block_gearup\tests\base_testcase;

/**
 * Objective exporter tests.
 *
 * @package block_gearup
 * @copyright 2026 Frédéric Massart
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_gearup\local\exporter\objective_exporter
 */
final class objective_exporter_test extends base_testcase {
    /**
     * Positions provider.
     *
     * @return array
     */
    public static function positions_provider(): array {
        return [
            'only' => [[1], 0, true, true],
            'first of two' => [[2, 1], 1, true, false],
            'last of two' => [[2, 1], 0, false, true],
            'first' => [[5, 1, 3], 1, true, false],
            'middle' => [[5, 1, 3], 2, false, false],
            'last' => [[5, 1, 3], 0, false, true],
            'first tied' => [[5, 5, 5], 0, true, false],
            'middle tied' => [[5, 5, 5], 1, false, false],
            'last tied' => [[5, 5, 5], 2, false, true],
        ];
    }

    /**
     * Test positions.
     *
     * @param array $positions Positions.
     * @param int $index Index.
     * @param bool $first Is first.
     * @param bool $last Is last.
     * @dataProvider positions_provider
     */
    public function test_positions(array $positions, int $index, bool $first, bool $last): void {
        global $PAGE;
        $model = $this->generator->create_mission_model();
        $ids = [];
        foreach ($positions as $position) {
            $objective = $this->generator->create_objective_model(['missionid' => $model->get('id'), 'type' => 'manual']);
            $objective->set('sortorder', $position)->update();
            $ids[] = $objective->get('id');
        }
        $mission = di::get('repository')->get_mission($model->get('id'));
        $exporter = new objective_exporter($mission->get_objective($ids[$index]), [
            'context' => $mission->get_context(), 'mission' => $mission,
        ]);
        $data = $exporter->export($PAGE->get_renderer('block_gearup'));
        $this->assertEquals($first, $data->isfirst);
        $this->assertEquals($last, $data->islast);
        $this->assertEquals($ids[$index], $data->id);
    }
}
