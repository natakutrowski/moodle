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
use context;

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_watched extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, ''),
            'duration' => new external_value(PARAM_INT, ''),
            'sourceid' => new external_value(PARAM_RAW, ''),
        ]);
    }

    /**
     * External function.
     *
     * @param int $contextid The context ID.
     * @param int $duration The duration.
     * @param string $sourceid The source ID.
     * @return void
     */
    public static function execute($contextid, $duration, $sourceid) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['contextid' => $contextid,
            'duration' => $duration, 'sourceid' => $sourceid]);
        $contextid = $params['contextid'];
        $duration = $params['duration'];
        $sourceid = $params['sourceid'];

        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        // Validate permissions.
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_access();

        // Broadcast the action.
        $action = new \block_gearup\local\action\time_watched($USER->id, $context, $duration, $sourceid);
        di::get('action_processor')->process_action($action);

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
