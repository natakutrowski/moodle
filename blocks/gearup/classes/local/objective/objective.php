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
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective;

use block_gearup\local\objective\type\type;

/**
 * Objective interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface objective {

    /**
     * Get the ID.
     *
     * @return integer
     */
    public function get_id(): int;

    /**
     * Get the count needed.
     *
     * @return integer
     */
    public function get_count_needed(): int;

    /**
     * Get the label.
     *
     * @return string
     */
    public function get_label(): string;

    /**
     * Get the mission ID.
     *
     * @return integer
     */
    public function get_mission_id(): int;

    /**
     * Get the type.
     *
     * @return type
     */
    public function get_type(): type;

    /**
     * Get the type config.
     *
     * @return mixed
     */
    public function get_type_config();

    // /**
    // * Check whether the action is acceptable.
    // *
    // * Based on the current configuration of the action, checks whether we
    // * can accept the action. This is meant to check the overall configurable
    // * aspects of acceptable, such as context ID, user ID, etc.
    // *
    // * The objective instance, belonging to a particular subject, will then
    // * perform further checks based on information that is specific to
    // * that instance of the objective.
    // *
    // * This should also validate that the action is compatible by deferring the
    // * check to {@link type::is_action_compatible}.
    // *
    // * @param action $action The action.
    // * @return boolean
    // */
    // public function is_action_acceptable(action $action): bool;

}
