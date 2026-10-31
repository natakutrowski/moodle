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

namespace block_gearup\local\model;

use block_gearup\tests\base_testcase;

/**
 * Objective ordering tests.
 *
 * @package block_gearup
 * @copyright 2026 Frédéric Massart
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_gearup\local\model\objective
 */
final class objective_test extends base_testcase {
    /**
     * Move provider.
     *
     * @return array
     */
    public static function move_provider(): array {
        return [
            'single up' => [[1], 0, -1, [0], [1]],
            'single down' => [[1], 0, 1, [0], [1]],
            'two up' => [[1, 2], 1, -1, [1, 0], [2, 1]],
            'two down' => [[1, 2], 0, 1, [1, 0], [2, 1]],
            'first up' => [[1, 2, 3], 0, -1, [0, 1, 2], [1, 2, 3]],
            'last down' => [[1, 2, 3], 2, 1, [0, 1, 2], [1, 2, 3]],
            'middle up' => [[1, 2, 3, 4], 2, -1, [0, 2, 1, 3], [1, 3, 2, 4]],
            'middle down' => [[1, 2, 3, 4], 1, 1, [0, 2, 1, 3], [1, 3, 2, 4]],
            'gap up' => [[1, 4, 10, 15], 2, -1, [0, 2, 1, 3], [1, 5, 4, 15]],
            'gap down' => [[1, 4, 10, 15], 1, 1, [0, 2, 1, 3], [1, 10, 9, 15]],
            'two ties up' => [[5, 5], 1, -1, [1, 0], [6, 5]],
            'two ties down' => [[5, 5], 0, 1, [1, 0], [5, 4]],
            'ties first up' => [[5, 5, 5], 0, -1, [0, 1, 2], [5, 5, 5]],
            'ties last down' => [[5, 5, 5], 2, 1, [0, 1, 2], [5, 5, 5]],
            // Ties use IDs and may displace objectives by more than one position.
            'ties middle up' => [[5, 5, 5, 5], 2, -1, [0, 2, 3, 1], [5, 6, 5, 5]],
            'ties middle down' => [[5, 5, 5, 5], 1, 1, [2, 0, 1, 3], [5, 5, 4, 5]],
            'ties surrounded up' => [[4, 5, 5, 5, 6], 2, -1, [0, 2, 3, 1, 4], [4, 6, 5, 5, 6]],
            'ties surrounded down' => [[4, 5, 5, 5, 6], 2, 1, [0, 3, 1, 2, 4], [4, 5, 5, 4, 6]],
            'neighbour ties up' => [[5, 5, 9], 2, -1, [0, 2, 1], [5, 6, 5]],
            'neighbour ties down' => [[9, 5, 9], 1, 1, [0, 1, 2], [8, 9, 9]],
            'noncreation order up' => [[3, 1, 2], 0, -1, [1, 0, 2], [2, 1, 3]],
            'noncreation order down' => [[3, 1, 2], 1, 1, [2, 1, 0], [3, 2, 1]],
            'zero ties up' => [[0, 0, 0], 1, -1, [1, 2, 0], [1, 0, 0]],
            'zero ties down' => [[0, 0, 0], 1, 1, [2, 0, 1], [0, 0, -1]],
            'lower ID into tied neighbour up' => [[9, 5, 5], 0, -1, [0, 1, 2], [5, 5, 6]],
            'higher ID into tied neighbour down' => [[9, 9, 5], 2, 1, [0, 1, 2], [8, 9, 9]],
            'up displacement collides' => [[6, 5, 6], 0, -1, [0, 1, 2], [5, 6, 6]],
            'down displacement collides' => [[5, 6, 5], 2, 1, [0, 1, 2], [5, 5, 6]],
        ];
    }

    /**
     * Test moves.
     *
     * @param array $positions Positions.
     * @param int $index Objective index.
     * @param int $direction Direction.
     * @param array $expectedorder Expected indexes.
     * @param array $expectedpositions Expected positions.
     * @dataProvider move_provider
     */
    public function test_move(array $positions, int $index, int $direction, array $expectedorder, array $expectedpositions): void {
        $mission = $this->generator->create_mission_model();
        $othermission = $this->generator->create_mission_model();
        $other = $this->generator->create_objective_model(['missionid' => $othermission->get('id'), 'type' => 'manual']);
        $objectives = [];
        foreach ($positions as $position) {
            $objective = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
            $objective->set('sortorder', $position);
            $objective->update();
            $objectives[] = $objective;
        }
        $ids = array_map(function ($objective) {
            return $objective->get('id');
        }, $objectives);

        if ($direction < 0) {
            $objectives[$index]->move_up();
        } else {
            $objectives[$index]->move_down();
        }

        $actual = objective::get_all_by_missionid($mission->get('id'));
        $this->assertEquals(array_map(function ($i) use ($ids) {
            return $ids[$i];
        }, $expectedorder), array_values(array_map(function ($objective) {
            return $objective->get('id');
        }, $actual)));
        $this->assertEquals($expectedpositions, array_map(function ($id) {
            return (new objective($id))->get('sortorder');
        }, $ids));
        $this->assertEquals((new objective($ids[$index]))->get('sortorder'), $objectives[$index]->get('sortorder'));
        $this->assertEquals(1, (new objective($other->get('id')))->get('sortorder'));
    }

    /**
     * Repeated moves provider.
     *
     * @return array
     */
    public static function repeated_moves_provider(): array {
        return [
            'consecutive' => [[1, 2, 3], [1, 2, 3]],
            'ties' => [[5, 5, 5], [5, 6, 6]],
            'gaps' => [[1, 10, 20], [1, 10, 11]],
        ];
    }

    /**
     * Test repeated moves.
     *
     * @param array $positions Positions.
     * @param array $expected Expected positions.
     * @dataProvider repeated_moves_provider
     */
    public function test_repeated_moves(array $positions, array $expected): void {
        $mission = $this->generator->create_mission_model();
        $a = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $b = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $c = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $a->set('sortorder', $positions[0])->update();
        $b->set('sortorder', $positions[1])->update();
        $c->set('sortorder', $positions[2])->update();

        $c->move_up();
        $c->move_up();
        $c->move_down();
        $c->move_down();

        $this->assertEquals([$a->get('id'), $b->get('id'), $c->get('id')], array_values(array_map(function ($objective) {
            return $objective->get('id');
        }, objective::get_all_by_missionid($mission->get('id')))));
        $this->assertEquals($expected, array_values(array_map(function ($objective) {
            return $objective->get('sortorder');
        }, objective::get_all_by_missionid($mission->get('id')))));
    }

    /**
     * Test unsaved moves.
     */
    public function test_unsaved_move(): void {
        $this->expectException(\coding_exception::class);
        (new objective())->move_up();
    }

    /**
     * Delete provider.
     *
     * @return array
     */
    public static function delete_provider(): array {
        return [
            'only' => [[1], 0, []],
            'first of two' => [[1, 2], 0, [1]],
            'last of two' => [[1, 2], 1, [1]],
            'first' => [[1, 2, 3], 0, [1, 2]],
            'middle' => [[1, 2, 3], 1, [1, 2]],
            'last' => [[1, 2, 3], 2, [1, 2]],
            'gaps' => [[2, 5, 9], 1, [2, 8]],
            'ties' => [[1, 2, 2, 3], 1, [1, 2, 3]],
            'ties with earlier ID after deletion' => [[3, 2, 2, 1], 2, [1, 2, 3]],
            'only ties' => [[5, 5, 5], 1, [5, 5]],
            'noncreation order' => [[3, 1, 2], 2, [1, 2]],
        ];
    }

    /**
     * Test deletion.
     *
     * @param array $positions Positions.
     * @param int $index Deleted index.
     * @param array $expected Expected positions.
     * @dataProvider delete_provider
     */
    public function test_delete(array $positions, int $index, array $expected): void {
        $mission = $this->generator->create_mission_model();
        $othermission = $this->generator->create_mission_model();
        $other = $this->generator->create_objective_model(['missionid' => $othermission->get('id'), 'type' => 'manual']);
        $other->set('sortorder', 20)->update();
        $objectives = [];
        foreach ($positions as $position) {
            $objective = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
            $objective->set('sortorder', $position)->update();
            $objectives[] = $objective;
        }
        $objectives[$index]->delete();
        unset($objectives[$index]);
        usort($objectives, function ($a, $b) {
            return [$a->get('sortorder'), $a->get('id')] <=> [$b->get('sortorder'), $b->get('id')];
        });
        $remaining = objective::get_all_by_missionid($mission->get('id'));
        $this->assertEquals(array_values(array_map(function ($objective) {
            return $objective->get('id');
        }, $objectives)), array_values(array_map(function ($objective) {
            return $objective->get('id');
        }, $remaining)));
        $this->assertEquals($expected, array_values(array_map(function ($objective) {
            return $objective->get('sortorder');
        }, $remaining)));
        $this->assertEquals(20, (new objective($other->get('id')))->get('sortorder'));
    }

    /**
     * Test appending.
     */
    public function test_create_appends_within_mission(): void {
        $mission = $this->generator->create_mission_model();
        $othermission = $this->generator->create_mission_model();
        $other = $this->generator->create_objective_model(['missionid' => $othermission->get('id'), 'type' => 'manual']);
        $other->set('sortorder', 100)->update();
        $a = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $this->assertEquals(1, $a->get('sortorder'));
        $a->set('sortorder', 7)->update();
        $b = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $this->assertEquals(8, $b->get('sortorder'));
        $b->delete();
        $c = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $this->assertEquals(8, $c->get('sortorder'));
    }

    /**
     * Test explicit positions.
     */
    public function test_create_preserves_explicit_sortorder(): void {
        $mission = $this->generator->create_mission_model();
        $objective = new objective(0, (object) [
            'missionid' => $mission->get('id'), 'type' => 'manual', 'label' => 'Manual', 'sortorder' => 42,
        ]);
        $objective->create();
        $this->assertEquals(42, (new objective($objective->get('id')))->get('sortorder'));
    }
}
