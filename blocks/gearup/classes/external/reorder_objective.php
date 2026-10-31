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
// See <https://levelup.plus>.

namespace block_gearup\external;

use block_gearup\di;

/**
 * Reorder an objective within its mission.
 *
 * @package    block_gearup
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reorder_objective extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'objectiveid' => new external_value(PARAM_INT, 'The objective ID'),
            'direction' => new external_value(PARAM_ALPHA, 'Either up or down'),
        ]);
    }

    /**
     * Reorder the objective.
     *
     * @param int $objectiveid The objective ID.
     * @param string $direction Either up or down.
     * @return bool
     */
    public static function execute($objectiveid, $direction) {
        $params = self::validate_parameters(self::execute_parameters(), [
            'objectiveid' => $objectiveid,
            'direction' => $direction,
        ]);
        if (!in_array($params['direction'], ['up', 'down'], true)) {
            throw new \invalid_parameter_exception('Direction must be up or down.');
        }

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        $repository = di::get('repository');
        $objective = $repository->get_objective($params['objectiveid']);
        if (!$objective || !$objective->get_id()) {
            throw new \invalid_parameter_exception('The objective does not exist.');
        }
        $mission = $repository->get_mission($objective->get_mission_id());
        $context = $mission->get_context();
        self::validate_context($context);
        di::get('access_permissions_factory')->get_permissions_for_context($context)->require_manage();

        $operator = di::get('mission_operator');
        if ($params['direction'] === 'up') {
            $operator->move_objective_up($mission, $objective->get_id());
        } else {
            $operator->move_objective_down($mission, $objective->get_id());
        }

        return true;
    }

    /**
     * Returns.
     *
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, 'Whether the request succeeded');
    }
}
