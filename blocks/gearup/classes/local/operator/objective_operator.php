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
 * Operator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\operator;

use block_gearup\local\action\action;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\type\type_with_state_initialisation;
use block_gearup\local\objective\type\type_with_state_reevaluation;

/**
 * Operator.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_operator {

    public function evaluate_action_on_instance(action $action, mission_instance $missioninst, objective_instance $objinst) {
        if ($objinst->is_completed()) {
            return;
        }
        $type = $objinst->get_objective()->get_type();
        if (!$type->is_action_passing_constraints($action, $objinst, $missioninst)) {
            return;
        }
        $type->consume_action($action, $objinst, $missioninst);
        $this->update_objective_instance_state($objinst);
    }

    public function initialise_state_on_instance(mission_instance $missioninst, objective_instance $objinst) {
        if ($objinst->is_completed()) {
            return;
        }
        $type = $objinst->get_objective()->get_type();
        if ($type instanceof type_with_state_initialisation) {
            $type->initialise_state($objinst, $missioninst);
        }
        $this->update_objective_instance_state($objinst);
    }

    public function increment_instance_counter(objective_instance $objinst, int $counter) {
        if ($counter <= 0) {
            throw new \coding_exception('Invalid counter to increment by.');
        } else if ($objinst->is_completed()) {
            throw new \coding_exception('Cannot increment counter of completed objective.');
        }
        $objinst->increment_counter($counter);
        $this->update_objective_instance_state($objinst);
    }

    /**
     * Re-evaluate the state of an objective instance.
     *
     * This is compatible with the type_with_state_reevaluation, but it can also be called
     * when we want to simply re-evaluate the state based on the counter. Implementations
     * that use state_reevaluation must not expect that the reevaluation happens when the
     * state is stale, it may happened sooner.
     *
     * @param objective_instance $objinst
     */
    public function reevaluate_state(objective_instance $objinst) {
        if ($objinst->is_completed()) {
            return;
        }
        $type = $objinst->get_objective()->get_type();
        if ($type instanceof type_with_state_reevaluation) {
            $type->reevaluate_state($objinst);
        }
        $this->update_objective_instance_state($objinst);
    }

    public function reset_instance(objective_instance $objinst) {
        // TODO We should probably not do this when the mission is complete!
        // TODO Maybe we do not need a ::reset() method on the objective instance.
        $objinst->reset();
    }

    protected function update_objective_instance_state(objective_instance $objinst) {
        if ($objinst->is_completed()) {
            return;
        }
        if ($objinst->get_counter() >= $objinst->get_objective()->get_count_needed()) {
            $objinst->mark_complete();
        }
    }

}
