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
 * Assigner type.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\assigner\type;

use block_gearup\local\assigner\assigner;
use block_gearup\local\mission\mission;
use lang_string;

/**
 * Assigner type interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type {

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
     * Get eligible users SQL.
     *
     * The query MUST only return a single field named `id` representing
     * the IDs of the users that are elligible. At this point, the assigner
     * does not need to worry whether the user may already be assigned, it
     * just needs to return who matches.
     *
     * I was today's years old when I learned that eligible is written with one l...
     *
     * @param assigner $assigner The assigner.
     * @param misison $mission The mission.
     * @return array With [$sql, $params]
     */
    public function get_elligible_users_sql(assigner $assigner, mission $mission): array;

    /**
     * Get a short description.
     *
     * This will be displayed to the educators to understand what this assigner does.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string;

}
