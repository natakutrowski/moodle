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
 * External.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\external;

use block_gearup\di;

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish_mission extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'missioninstid' => new external_value(PARAM_INT, ''),
        ]);
    }

    /**
     * External function.
     *
     * @param int $missioninstid The mission instance ID.
     * @return void
     */
    public static function execute($missioninstid) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['missioninstid' => $missioninstid]);
        $missioninstid = $params['missioninstid'];

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        $mr = di::get('repository');
        $missionhelper = di::get('mission_helper');
        $missioninst = $mr->get_instance($missioninstid);
        $context = $missioninst->get_mission()->get_context();
        self::validate_context($context);

        // Validate permissions.
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_access();

        // Check is user's mission.
        if ($missioninst->get_subject_id() != $USER->id) {
            throw new \moodle_exception('invalidaccess', 'block_gearup');
        }

        // Validate mission state.
        if (!$missionhelper->is_completed($missioninst)) {
            throw new \moodle_exception('invalidstate', 'block_gearup');
        }

        $mo = di::get('mission_operator');
        $mo->finish_instance($missioninst);

        return true;
    }

    /**
     * Returns.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, '');
    }

}
