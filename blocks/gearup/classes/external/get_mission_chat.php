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
use block_gearup\local\exporter\mission_chat_exporter;

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_mission_chat extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'missionid' => new external_value(PARAM_INT, ''),
            'state' => new external_value(PARAM_ALPHANUMEXT, 'One of assigned, started, completed, or ended'),
            'needsattention' => new external_value(PARAM_BOOL, ''),
        ]);
    }

    /**
     * External function.
     *
     * @param int $missionid The mission ID.
     * @return void
     */
    public static function execute($missionid, $state, $needsattention) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'missionid' => $missionid, 'state' => $state, 'needsattention' => $needsattention, ]);
        $missionid = $params['missionid'];
        $state = $params['state'];
        $needsattention = $params['needsattention'];

        $mr = di::get('repository');
        $mission = $mr->get_mission($missionid);
        $context = $mission->get_context();
        self::validate_context($context);

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        // Validate permissions.
        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_manage();

        $hasstarted = false;
        $hascompleted = false;
        $isended = false;
        if ($state === 'started' || $state === 'completed' || $state === 'ended') {
            $hasstarted = true;
        }
        if ($state === 'completed' || $state === 'ended') {
            $hascompleted = true;
        }
        if ($state === 'ended') {
            $isended = true;
        }

        $exporter = new mission_chat_exporter($mission, [
            'mission_helper' => di::get('mission_helper'),
            'state' => (object) [
                'hasstarted' => $hasstarted,
                'hascompleted' => $hascompleted,
                'isended' => $isended,
                'needsattention' => $needsattention,
            ],
            'user' => $USER,
        ]);
        return $exporter->export(di::get('renderer'));
    }

    /**
     * Returns.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return mission_chat_exporter::get_read_structure();
    }

}
