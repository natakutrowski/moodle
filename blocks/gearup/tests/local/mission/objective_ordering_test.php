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

namespace block_gearup\local\mission;

use block_gearup\di;
use block_gearup\local\model\mission_inst;
use block_gearup\local\model\objective_inst;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\objective\persisted_objective_instance;
use block_gearup\tests\base_testcase;

/**
 * Objective collection tests.
 *
 * @package block_gearup
 * @copyright 2026 Frédéric Massart
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_gearup\local\mission\persisted_mission
 * @covers \block_gearup\local\mission\persisted_mission_instance
 * @covers \block_gearup\local\repository\repository
 * @covers \block_gearup\local\operator\mission_operator
 */
final class objective_ordering_test extends base_testcase {
    /**
     * Collections provider.
     *
     * @return array
     */
    public static function collections_provider(): array {
        return [
            'empty direct' => [[], [], false],
            'empty lazy' => [[], [], true],
            'single direct' => [[5], [0], false],
            'single lazy' => [[5], [0], true],
            'two direct' => [[2, 1], [1, 0], false],
            'two lazy' => [[2, 1], [1, 0], true],
            'ties direct' => [[9, 2, 2, 1], [3, 1, 2, 0], false],
            'ties lazy' => [[9, 2, 2, 1], [3, 1, 2, 0], true],
        ];
    }

    /**
     * Test collections.
     *
     * @param array $positions Positions.
     * @param array $order Expected indexes.
     * @param bool $lazy Use lazy loading.
     * @dataProvider collections_provider
     */
    public function test_collections(array $positions, array $order, bool $lazy): void {
        $model = $this->generator->create_mission_model();
        $objectives = [];
        $instances = [];
        foreach ($positions as $index => $position) {
            $objective = $this->generator->create_objective_model(['missionid' => $model->get('id'), 'type' => 'manual']);
            $objective->set('sortorder', $position)->update();
            $objectives["key$index"] = new persisted_objective($objective, di::get('objective_type_resolver'));
            $instances["key$index"] = new persisted_objective_instance(new objective_inst(), $objectives["key$index"]);
        }
        $originalobjectives = $objectives;
        $originalinstances = $instances;
        $mission = new persisted_mission($model, $lazy ? function () use ($objectives) {
            return $objectives;
        } : $objectives);
        $expectedkeys = array_map(function ($index) {
            return "key$index";
        }, $order);
        $this->assertEquals($expectedkeys, array_keys($mission->get_objectives()));
        $this->assertEquals($expectedkeys, array_keys($mission->get_objectives()));
        $this->assertSame($originalobjectives, $objectives);

        $instance = new persisted_mission_instance(new mission_inst(), $mission, $instances);
        $this->assertEquals($expectedkeys, array_keys($instance->get_objective_instances()));
        $this->assertSame($originalinstances, $instances);
        $instance->set_objective_instances(array_reverse($instances, true));
        $this->assertEquals($expectedkeys, array_keys($instance->get_objective_instances()));
        $instance->set_objective_instances([]);
        $this->assertEquals([], $instance->get_objective_instances());

        $expectedids = array_map(function ($key) use ($objectives) {
            return $objectives[$key]->get_id();
        }, $expectedkeys);
        $repository = di::get('repository');
        $this->assertEquals($expectedids, array_values(array_map(function ($objective) {
            return $objective->get_id();
        }, $repository->get_objectives($model->get('id')))));
        $this->assertEquals($expectedids, array_values(array_map(function ($objective) {
            return $objective->get_id();
        }, $repository->get_mission($model->get('id'))->get_objectives())));
    }

    /**
     * Test assignment and reload.
     */
    public function test_assignment_and_reload(): void {
        $user = $this->getDataGenerator()->create_user();
        $mission = $this->generator->create_persisted_mission(['objectives' => [
            ['type' => 'manual'], ['type' => 'manual'], ['type' => 'manual'],
        ]]);
        $objectives = array_values($mission->get_objectives());
        $objectives[2]->get_persistent()->move_up();
        $objectives[2]->get_persistent()->move_up();
        $repository = di::get('repository');
        $mission = $repository->get_mission($mission->get_id());
        $instance = di::get('mission_operator')->assign_mission($mission, $user->id);
        $expected = [$objectives[2]->get_id(), $objectives[0]->get_id(), $objectives[1]->get_id()];
        $this->assertEquals($expected, array_values(array_map(function ($instance) {
            return $instance->get_objective()->get_id();
        }, $instance->get_objective_instances())));

        // Reorder after assignment: instance IDs now differ from objective order.
        $objectives[2]->get_persistent()->move_down();
        $expected = [$objectives[0]->get_id(), $objectives[2]->get_id(), $objectives[1]->get_id()];
        $reloaded = $repository->get_instance($instance->get_id());
        $this->assertEquals($expected, array_values(array_map(function ($instance) {
            return $instance->get_objective()->get_id();
        }, $reloaded->get_objective_instances())));
    }

    /**
     * Test adding an objective to an existing instance.
     */
    public function test_update_instance_objectives(): void {
        $user = $this->getDataGenerator()->create_user();
        $mission = $this->generator->create_persisted_mission(['objectives' => [
            ['type' => 'manual'], ['type' => 'manual'],
        ]]);
        $operator = di::get('mission_operator');
        $instance = $operator->assign_mission($mission, $user->id);
        $original = array_values($mission->get_objectives());
        $new = $this->generator->create_objective_model(['missionid' => $mission->get_id(), 'type' => 'manual']);
        $new->move_up();
        $new->move_up();
        $instance = di::get('repository')->get_instance($instance->get_id());
        $operator->update_instance_objectives($instance);
        $expected = [$new->get('id'), $original[0]->get_id(), $original[1]->get_id()];
        $this->assertEquals($expected, array_values(array_map(function ($instance) {
            return $instance->get_objective()->get_id();
        }, $instance->get_objective_instances())));
        $instance = di::get('repository')->get_instance($instance->get_id());
        $this->assertEquals($expected, array_values(array_map(function ($instance) {
            return $instance->get_objective()->get_id();
        }, $instance->get_objective_instances())));
    }
}
