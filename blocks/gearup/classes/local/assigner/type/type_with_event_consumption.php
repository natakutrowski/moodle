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

use core\event\base as event;
use block_gearup\local\assigner\assigner;
use block_gearup\local\mission\mission;

/**
 * Assigner type interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type_with_event_consumption extends type {

    /**
     * Get the eligible users from the event.
     *
     * This will only be called when the event is compatible and passes constraints.
     *
     * I was today's years old when I learned that eligible is written with one l... And I also
     * spotted that the method name is misspelled... embarrassing...
     *
     * @param event $event The event.
     * @param assigner $assigner The assigner.
     * @param mission $mission The mission.
     * @return array With [$sql, $params] Returning 1 field aliased `id`. See {@link type::get_elligible_users_sql}.
     */
    public function get_elligile_users_sql_from_event(event $event, assigner $assigner, mission $mission): array;

    /**
     * Whether the event is compatible.
     *
     * This should always return the same value regardless of the inner
     * properties of the event instance.
     *
     * @param event $event The event.
     * @return bool
     */
    public function is_event_compatible(event $event): bool;

    /**
     * Whether the event is passing the assigner's constraints.
     *
     * We do not pass the mission here because contraints should be evaluated
     * based on the config of the assigner, and nothing else.
     *
     * @param event $event The event.
     * @param assigner $assigner The assigner.
     * @return bool
     */
    public function is_event_passing_constraints(event $event, assigner $assigner): bool;

}
