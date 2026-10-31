<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\external;

use block_xp\di;
use block_xp\external\external_description;
use block_xp\external\external_function_parameters;
use block_xp\external\external_value;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\user_utils;
use coding_exception;
use moodle_exception;

/**
 * External function.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_state extends external_api {
    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, '', VALUE_DEFAULT),
            'userid' => new external_value(PARAM_INT, '', VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, '', VALUE_DEFAULT),
        ]);
    }

    /**
     * External function.
     *
     * @param ?int $courseid The course ID.
     * @param int $userid The user ID.
     * @param ?int $contextid The context ID.
     * @return object
     */
    public static function execute($courseid = null, $userid = 0, $contextid = null) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'userid', 'contextid'));
        extract($params); // @codingStandardsIgnoreLine

        $userid = $userid ? $userid : $USER->id;
        if (!empty($contextid)) {
            $world = di::get('context_world_factory')->get_world_from_context(\context::instance_by_id($contextid));
        } else {
            $world = di::get('course_world_factory')->get_world($courseid ?? 0);
        }
        self::validate_context($world->get_context());
        di::get('addon')->require_activated();

        $accessperms = $world->get_access_permissions();
        $accessperms->require_access();
        if ($userid != $USER->id) {
            if (!$accessperms instanceof access_report_permissions) {
                throw new coding_exception('Access permissions object requires report permissions.');
            }
            $accessperms->require_access_report();
            if (!user_utils::is_valid_target($world->get_context(), $userid)) {
                throw new moodle_exception('invaliduser', 'core_error');
            }
        }

        $store = $world->get_store();
        $state = $store->get_state($userid);

        return self::serialize_state($state);
    }

    /**
     * External function return definition.
     *
     * @return external_description
     */
    public static function execute_returns() {
        return self::state_description();
    }
}
