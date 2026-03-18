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

use block_gearup\local\model\mission;

/**
 * Behat plugin generator
 *
 * @package    block_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_block_gearup_generator extends behat_generator_base {

    protected function get_creatable_entities(): array {
        return [
            'achievements' => [
                'datagenerator' => 'achievement',
                'required' => ['title'],
                'switchids' => ['course' => 'courseid'],
            ],
            'challenges' => [
                'datagenerator' => 'challenge',
                'required' => ['title'],
                'switchids' => ['course' => 'courseid'],
            ],
            'quests' => [
                'datagenerator' => 'quest',
                'required' => ['title'],
                'switchids' => ['course' => 'courseid'],
            ],
            'recruits' => [
                'datagenerator' => 'recruit',
                'required' => ['mission', 'user'],
                'switchids' => [
                    'mission' => 'missionid',
                    'user' => 'subjectid',
                ],
            ],
            'streaks' => [
                'datagenerator' => 'streak',
                'required' => ['title'],
                'switchids' => ['course' => 'courseid'],
            ],
        ];
    }

    /**
     * Get the mission ID.
     *
     * @param string $mission The mission name
     * @return int The ID
     */
    protected function get_mission_id(string $mission): int {
        return (int) mission::get_record(['title' => $mission], MUST_EXIST)->get('id');
    }

}
