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
 * Objective type.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use lang_string;

/**
 * Objective type interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type {

    /**
     * Consume the action.
     *
     * This method should increment the counter, and state of
     * the instance. It should only be called with compatible actions,
     * and after all checks have been made.
     *
     * @param action $action The action.
     * @param objective_instance $instance The instance of the objective.
     * @param mission_instance $missioninst The instance of the mission.
     */
    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst);

    /**
     * Get the config form extender.
     *
     * By convention, additional fields must be prefixed with `cd_` for "Config Data".
     *
     * @param mission $mission The mission.
     * @param objective|null $objective The objective, when editing.
     */
    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender;

    /**
     * Get the display name.
     *
     * @return lang_string
     */
    public function get_display_name(): lang_string;

    /**
     * Get a short description.
     *
     * This will be displayed to the educators to understand what this outcome does.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string;

    // /**
    // * Get the state structure.
    // *
    // * @return external_description
    // */
    // public function get_state_structure(): \external_description;

    /**
     * Whether the objective type is compatible with the action.
     *
     * @param action $action The action.
     * @return boolean
     */
    public function is_action_compatible(action $action): bool;

    /**
     * Validate the constraints of the objective.
     *
     * @param action $action The action.
     * @param objective_instance $instance The instance of the objective.
     * @param mission_instance $missioninst The instance of the mission.
     * @return bool
     */
    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool;

}
