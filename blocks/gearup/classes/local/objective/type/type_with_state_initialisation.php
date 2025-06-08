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
 * Objective type with state initialisation.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective_instance;
use lang_string;

/**
 * Objective type with state initialisation interface.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface type_with_state_initialisation extends type {

    /**
     * Initialise state.
     *
     * This method can be used to initialise the state, or counter, of the
     * objective instance. This is useful when the state can resume from
     * the data in the system, or when it needs to collect data and save
     * it in its state.
     *
     * @param objective_instance $instance The instance of the objective.
     * @param mission_instance missioinst The instance of the associated mission.
     */
    public function initialise_state(objective_instance $instance, mission_instance $missioninst);

}
