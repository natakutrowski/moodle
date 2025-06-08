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
 * Adhoc task.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\task;

use block_gearup\di;
use block_gearup\local\mission\mission_instance;

/**
 * Adhoc task.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_assignmentbehaviour_update_adhoc extends \core\task\adhoc_task {

    public function execute() {
        // Must contain missionid.
        $data = $this->get_custom_data();

        $missionoperator = di::get('mission_operator');
        $repository = di::get('repository');
        $missionhelper = di::get('mission_helper');
        $mission = $repository->get_mission($data->missionid);

        if (!$mission) {
            debugging('The mission no longer exists.', DEBUG_DEVELOPER);
            return;
        } else if (!$missionhelper->is_active($mission)) {
            debugging('The mission is no longer active.', DEBUG_DEVELOPER);
            return;
        }

        // We only need to do something when the mission is compulsory.
        if (!$missionhelper->is_compulsory($mission)) {
            return;
        }

        // Start each instance.
        $missioninsts = $repository->get_instances($mission->get_id(), 0, 0, [], mission_instance::STATE_ASSIGNED);
        foreach ($missioninsts as $missioninst) {
            if (!$missionhelper->has_started($missioninst)) {
                $missionoperator->start_instance($missioninst);
            }
        }
    }

}
