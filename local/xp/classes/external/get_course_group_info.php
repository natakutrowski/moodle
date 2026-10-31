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

/**
 * External function.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\external;

use block_xp\di;
use block_xp\external\external_description;
use block_xp\external\external_function_parameters;
use block_xp\external\external_multiple_structure;
use block_xp\external\external_single_structure;
use block_xp\external\external_value;
use block_xp\local\group\group_policy;
use context_course;

/**
 * External function.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_group_info extends external_api {
    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * External function.
     *
     * @param int $courseid The course ID.
     * @return object
     */
    public static function execute($courseid) {
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid'));
        extract($params); // @codingStandardsIgnoreLine

        $context = context_course::instance($courseid);
        self::validate_context($context);
        di::get('addon')->require_activated();

        $grouppolicy = group_policy::from_context($context);
        $groups = $grouppolicy->get_selectable_groups();
        $allparticipantskey = false;
        foreach ($groups as $key => $group) {
            if ($group->id === 0) {
                $allparticipantskey = $key;
                break;
            }
        }

        $canseeallparticipants = false;
        if ($allparticipantskey !== false) {
            unset($groups[$allparticipantskey]);
            $canseeallparticipants = true;
        }

        return [
            'canaccessallgroups' => $grouppolicy->can_access_all_groups(),
            'canseeallparticipants' => $canseeallparticipants,
            'groupmode' => $grouppolicy->get_group_mode(),
            'groups' => array_map(function ($group) {
                return (array) $group;
            }, $groups),
        ];
    }

    /**
     * External function return definition.
     *
     * @return external_description
     */
    public static function execute_returns() {
        return new external_single_structure([
            'canaccessallgroups' => new external_value(PARAM_BOOL, 'Whether the user has the permission to access all groups.'),
            'canseeallparticipants' => new external_value(PARAM_BOOL, 'Whether the user can see "All participants".'),
            'groupmode' => new external_value(PARAM_INT),
            'groups' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT),
                'name' => new external_value(PARAM_RAW),
                'ismember' => new external_value(PARAM_BOOL),
            ]), 'The groups that the current user can access.'),
        ]);
    }
}
