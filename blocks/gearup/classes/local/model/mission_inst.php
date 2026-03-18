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
 * Mission instance persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\model;

use block_gearup\local\mission\mission_instance;
use core\persistent;

/**
 * Mission instance persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_inst extends persistent {

    const TABLE = 'block_gearup_mission_inst';

    /**
     * Properties.
     *
     * @return array
     */
    public static function define_properties() {
        return [
            'missionid' => [
                'type' => PARAM_INT,
            ],
            'subjectid' => [
                'type' => PARAM_INT,
            ],
            'counter' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'iteration' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'state' => [
                'type' => PARAM_INT,
                'choices' => [
                    mission_instance::STATE_ASSIGNED,
                    mission_instance::STATE_STARTED,
                    mission_instance::STATE_COMPLETED,
                    mission_instance::STATE_ENDED,
                ],
                'default' => mission_instance::STATE_ASSIGNED,
            ],
            'completionratio' => [
                'type' => PARAM_FLOAT,
                'default' => 0,
            ],
            'deadline' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'needsattention' => [
                'type' => PARAM_BOOL,
                'default' => 0,
            ],
            'timestarted' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'timecompleted' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'timeended' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * After create hook.
     *
     * @return void
     */
    public function after_create() {
        global $DB;

        // Special case for tests to force the timecreated value until core uses the clock in the persistent class.
        if (PHPUNIT_TEST && interface_exists(\core\clock::class)) {
            $clocktime = \core\di::get(\core\clock::class)->time();
            if ($clocktime != $this->get('timecreated')) {
                $this->set('timecreated', $clocktime);
                $DB->set_field(static::TABLE, 'timecreated', $clocktime, ['id' => $this->get('id')]);
            }
        }
    }

    /**
     * Count the number of unique subjects by mission ID.
     *
     * @param int $missionid The mission ID.
     * @return int
     */
    public static function count_subjects_by_missionid(int $missionid) {
        global $DB;
        return $DB->count_records_select(static::TABLE, 'missionid = ?', [$missionid], 'COUNT(DISTINCT subjectid)');
    }

}
