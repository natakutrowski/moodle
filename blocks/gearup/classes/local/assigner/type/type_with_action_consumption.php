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

namespace block_gearup\local\assigner\type;

use block_gearup\local\action\action;
use block_gearup\local\assigner\assigner;
use block_gearup\local\mission\mission;

/**
 * Assigner type interface.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type_with_action_consumption extends type {

    /**
     * Whether the action is compatible.
     *
     * @param action $action The action.
     * @return bool
     */
    public function is_action_compatible(action $action): bool;

    /**
     * Whether the action is passing the assigner's constraints.
     *
     * We do not pass the mission here because contraints should be evaluated
     * based on the config of the assigner, and nothing else.
     *
     * @param action $action The action.
     * @param assigner $assigner The assigner.
     * @return bool
     */
    public function is_action_passing_constraints(action $action, assigner $assigner): bool;

}
