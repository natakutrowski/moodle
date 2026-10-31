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

namespace block_gearup\external;

use block_gearup\local\mission\mission;
use block_gearup\local\model\objective;
use block_gearup\tests\base_testcase;

/**
 * Reorder objective tests.
 *
 * @package block_gearup
 * @copyright 2026 Frédéric Massart
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \block_gearup\external\reorder_objective
 * @covers \block_gearup\local\operator\mission_operator
 */
final class reorder_objective_test extends base_testcase {
    /**
     * Directions provider.
     *
     * @return array
     */
    public static function directions_provider(): array {
        return [['up', 1], ['down', 0]];
    }

    /**
     * Test reordering.
     *
     * @param string $direction Direction.
     * @param int $index Index.
     * @dataProvider directions_provider
     */
    public function test_reorder(string $direction, int $index): void {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $mission = $this->generator->create_mission_model(['courseid' => $course->id, 'objectives' => [
            ['type' => 'manual'], ['type' => 'manual'],
        ]]);
        $objectives = array_values(objective::get_all_by_missionid($mission->get('id')));
        $this->setUser($teacher);
        $result = reorder_objective::execute($objectives[$index]->get('id'), $direction);
        $this->assertTrue(reorder_objective::clean_returnvalue(reorder_objective::execute_returns(), $result));
        $this->assertEquals(
            [$objectives[1]->get('id'), $objectives[0]->get('id')],
            array_values(array_map(function ($objective) {
                return $objective->get('id');
            }, objective::get_all_by_missionid($mission->get('id'))))
        );
    }

    /**
     * Permission provider.
     *
     * @return array
     */
    public static function permissions_provider(): array {
        return [
            'student' => ['student', false],
            'teacher in another course' => ['editingteacher', true],
        ];
    }

    /**
     * Test permissions.
     *
     * @param string $role Role.
     * @param bool $othercourse Manage another course.
     * @dataProvider permissions_provider
     */
    public function test_permissions(string $role, bool $othercourse): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $othercourse ? 'student' : $role);
        if ($othercourse) {
            $other = $this->getDataGenerator()->create_course();
            $this->getDataGenerator()->enrol_user($user->id, $other->id, $role);
        }
        $mission = $this->generator->create_mission_model(['courseid' => $course->id, 'objectives' => [
            ['type' => 'manual'], ['type' => 'manual'],
        ]]);
        $objectives = array_values(objective::get_all_by_missionid($mission->get('id')));
        $this->setUser($user);
        $this->expectException(\required_capability_exception::class);
        reorder_objective::execute($objectives[0]->get('id'), 'down');
    }

    /**
     * Invalid directions provider.
     *
     * @return array
     */
    public static function invalid_directions_provider(): array {
        return [['left'], ['UP'], [''], ['down1']];
    }

    /**
     * Test invalid directions.
     *
     * @param string $direction Direction.
     * @dataProvider invalid_directions_provider
     */
    public function test_invalid_direction(string $direction): void {
        $this->setAdminUser();
        $mission = $this->generator->create_mission_model();
        $objective = $this->generator->create_objective_model(['missionid' => $mission->get('id'), 'type' => 'manual']);
        $this->expectException(\invalid_parameter_exception::class);
        reorder_objective::execute($objective->get('id'), $direction);
    }

    /**
     * Missing objectives provider.
     *
     * @return array
     */
    public static function missing_objectives_provider(): array {
        return [[0], [-1], [99999999]];
    }

    /**
     * Test missing objectives.
     *
     * @param int $id Objective ID.
     * @dataProvider missing_objectives_provider
     */
    public function test_missing_objective(int $id): void {
        $this->setAdminUser();
        $this->expectException(\invalid_parameter_exception::class);
        reorder_objective::execute($id, 'up');
    }

    /**
     * Test archived missions.
     *
     * @param string $direction Direction.
     * @param int $index Index.
     * @dataProvider directions_provider
     */
    public function test_archived_mission(string $direction, int $index): void {
        $this->setAdminUser();
        $model = $this->generator->create_mission_model(['state' => mission::STATE_ARCHIVED, 'objectives' => [
            ['type' => 'manual'], ['type' => 'manual'],
        ]]);
        $objectives = array_values(objective::get_all_by_missionid($model->get('id')));
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('cannoteditarchivedmission', 'block_gearup'));
        reorder_objective::execute($objectives[$index]->get('id'), $direction);
    }
}
