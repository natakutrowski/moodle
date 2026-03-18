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
 * @package    availability_gearup
 * @category   test
 * @copyright  2025 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_availability_gearup_generator extends behat_generator_base {

    /**
     * Get creatable entities.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'restrictions' => [
                'datagenerator' => 'restriction',
                'required' => ['activity', 'mission', 'mode'],
                'switchids' => [
                    'activity' => 'cmid',
                    'mission' => 'missionid',
                ],
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
