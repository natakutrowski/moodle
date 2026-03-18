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

namespace block_gearup\external;

use block_gearup\di;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\user_utils;
use block_gearup\output\mission_instance_page;
use context;

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_mission_instance_page extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'missioninstid' => new external_value(PARAM_INT, ''),
            'returnto' => new external_value(PARAM_ALPHANUMEXT, ''),
            'gupagectxid' => new external_value(PARAM_INT, '', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * External function.
     *
     * @param int $missioninstid The mission instance ID.
     * @return void
     */
    public static function execute($missioninstid, $returnto, ?int $gupagectxid) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'missioninstid' => $missioninstid,
            'returnto' => $returnto,
            'gupagectxid' => $gupagectxid,
        ]);
        $missioninstid = $params['missioninstid'];
        $returnto = $params['returnto'];
        $gupagectxid = $params['gupagectxid'];

        $mr = di::get('repository');
        $missioninst = $mr->get_instance($missioninstid);
        $context = $missioninst->get_mission()->get_context();
        self::validate_context($context);

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        // Validate permissions.
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_manage();

        // Validate group access.
        if (course_utils::uses_group_mode($context) && !user_utils::can_view_all_participants($context, $USER->id)) {
            throw new \moodle_exception('accessnotpermittedcannotviewallparticipants', 'block_gearup');
        }

        // Re-implementation of our routing logic.
        $pagecontext = ($gupagectxid ? context::instance_by_id($gupagectxid, IGNORE_MISSING) : null) ?? null;
        if ($pagecontext instanceof \context_user) {
            $pagecontext = $pagecontext->get_parent_context();
        }

        $urlresolver = di::get('url_resolver_factory')->get_resolver_for_context($context, $pagecontext);
        $page = new mission_instance_page($missioninst, $urlresolver);
        $page->set_return_to($returnto);

        return $page->export_for_template(di::get('renderer'));
    }

    /**
     * Returns.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return mission_instance_page::get_read_structure();
    }

}
