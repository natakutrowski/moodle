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

use availability_gearup\condition;

/**
 * Data generator.
 *
 * @package    availability_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_gearup_generator extends component_generator_base {

    /**
     * Create a restriction.
     *
     * This is a hack to speed up behat testing by generating the restrictions
     * without needing to run many steps with JavaScript enabled.
     *
     * @param object|array $data The data.
     */
    public function create_restriction($data = null) {
        global $DB;
        $data = (object) ($data ?: []);

        $modesmap = [
            'isassigned' => condition::MODE_IS_ASSIGNED,

            'isstarted' => condition::MODE_IS_STARTED,
            'isongoing' => condition::MODE_IS_STARTED,

            'iscompleted' => condition::MODE_IS_COMPLETED,

            'isended' => condition::MODE_IS_ENDED,
            'isfinished' => condition::MODE_IS_ENDED,

            'hasbeenrecruitedfor' => condition::MODE_IS_RECRUIT,
            'hasstarted' => condition::MODE_HAS_STARTED,

            'hascompleted' => condition::MODE_HAS_COMPLETED,
            'hasbeencompleted' => condition::MODE_HAS_COMPLETED,
        ];

        if (!isset($data->cmid)) {
            throw new \coding_exception('Missing cmid');
        } else if (!isset($data->mode)) {
            throw new \coding_exception('Missing mode');
        } else if (!isset($modesmap[$data->mode])) {
            throw new \coding_exception('Invalid mode, use one of ' . implode(', ', array_keys($modesmap)));
        } else if (!isset($data->missionid)) {
            throw new \coding_exception('Missing missionid');
        }

        $availability = json_encode([
            'op' => '&',
            'showc' => [false],
            'c' => [
                [
                    'type' => 'gearup',
                    'missionid' => (int) $data->missionid,
                    'mode' => $modesmap[$data->mode],
                ],
            ],
        ]);
        $DB->set_field('course_modules', 'availability', $availability, ['id' => $data->cmid]);
    }

}
