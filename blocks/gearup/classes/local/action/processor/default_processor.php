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
 * Actions processor.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\action\processor;

use block_gearup\local\action\action;

/**
 * Actions processor.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class default_processor implements processor {

    protected $repository;
    protected $objtyperesolver;
    protected $missionoperator;
    protected $objoperator;

    // TODO Type annotations.
    public function __construct($repository, $objtyperesolver, $missionoperator, $objoperator) {
        $this->repository = $repository;
        $this->objtyperesolver = $objtyperesolver;
        $this->missionoperator = $missionoperator;
        $this->objoperator = $objoperator;
    }

    /**
     * Process the action.
     *
     * @param action $action The action.
     * @return void
     */
    public function process_action(action $action) {
        $types = $this->objtyperesolver->get_types_compatible_with_action($action);
        if (empty($types)) {
            return;
        }

        // We could optimise this, but that doesn't lead to a lot of optimisation. Essentially, we can cache
        // the objective types that are ongoing within the context path, and do a preliminary check to see if
        // we have an overlap, and only continue then. But, realistically we won't have a ton of actions
        // happening at once, so we would only be saving one or two database requests. Granted this might add
        // database requests to each request, but that shouldn't be significant at this stage. Moreover, intersecting
        // context paths would still cause a hit on this, for instance for incomplete types at the system level.
        // The complexity of realibly implementing this cache is also high as we would need to invalidate the cache
        // quite a few case scenario.
        $objinstances = $this->repository->get_incomplete_objective_instances_of_types($types, $action->get_user_id(),
            $action->get_context());
        if (empty($objinstances)) {
            return;
        }

        // Fetch the mission instances.
        // TODO Fetching the instance could be done as we resolve the incomplete objective instances.
        $missioninstids = array_unique(array_map(function($objinst) {
            return $objinst->get_mission_instance_id();
        }, $objinstances));
        $missioninsts = array_reduce($this->repository->get_instances_by_ids($missioninstids), function($carry, $mi) {
            $carry[$mi->get_id()] = $mi;
            return $carry;
        }, []);

        // Passing the action to all the objectives.
        foreach ($objinstances as $objinstance) {
            $missioninstid = $objinstance->get_mission_instance_id();
            $missioninst = $missioninsts[$missioninstid] ?? null;
            if (!$missioninst) {
                debugging("The mission instance $missioninstid was not found.", DEBUG_DEVELOPER);
                continue;   // This should never happen, but better be safe than sorry.
            }
            // There will be inconstencies errors if we do not pass the same objinstance as the one
            // contained within the mission instance. We should have a better system for this.
            // TODO Avoid having to do this manually!
            $objinstance = $missioninst->get_instance_of_objective($objinstance->get_objective()->get_id());
            $this->objoperator->evaluate_action_on_instance($action, $missioninst, $objinstance);
        }

        // Check whether a mission has changed state due to the objective being completed.
        foreach ($missioninsts as $missioninst) {
            $this->missionoperator->evaluate_instance($missioninst);
        }
    }
}
