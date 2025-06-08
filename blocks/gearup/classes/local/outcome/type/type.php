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
 * Outcome type.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use lang_string;

/**
 * Outcome type interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type {

    /**
     * Apply the outcome to the user by ID.
     *
     * @param outcome $outcome The outcome.
     * @param mission_instance $missioninst The mission instance.
     */
    public function apply(outcome $outcome, mission_instance $missioninst);

    /**
     * Get the config form extender.
     *
     * By convention, additional fields must be prefixed with `cd_` for "Config Data".
     *
     * @param mission $mission The mission.
     */
    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender;

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

}
