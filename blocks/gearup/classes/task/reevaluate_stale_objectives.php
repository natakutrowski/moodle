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
 * Task.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\task;

use block_gearup\di;

/**
 * Task.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reevaluate_stale_objectives extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('taskreevaluatestaleobjectives', 'block_gearup');
    }

    public function execute() {
        if (!di::get('lm')->is_active()) {
            return;
        }

        $repository = di::get('repository');
        $missionoperator = di::get('mission_operator');
        $objectiveoperator = di::get('objective_operator');
        $objinsts = $repository->get_stale_objective_instances();

        // Re-evaluate each objective.
        $checkmissioninstids = [];
        foreach ($objinsts as $objinst) {
            $objectiveoperator->reevaluate_state($objinst);
            $checkmissioninstids[] = $objinst->get_mission_instance_id();
        }

        // Check whether a mission has changed state due to the objective being completed.
        if (!empty($checkmissioninstids)) {
            $missioninsts = $repository->get_instances_by_ids(array_unique($checkmissioninstids));
            foreach ($missioninsts as $missioninst) {
                try {
                    $missionoperator->evaluate_instance($missioninst);
                } catch (\moodle_exception $e) {
                    debugging('Failed to evaluate mission instance ' . $missioninst->id . ': ' . $e->getMessage());
                }
            }
        }
    }

}
