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

/**
 * Adhoc task.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigner_sync_adhoc extends \core\task\adhoc_task {

    public function execute() {
        // Must contain missionid and assignerid.
        $data = $this->get_custom_data();

        $missionoperator = di::get('mission_operator');
        $repository = di::get('repository');

        $mission = $repository->get_mission($data->missionid);
        if (!$mission) {
            debugging('The mission no longer exists.', DEBUG_DEVELOPER);
            return;
        } else if (!di::get('mission_helper')->is_active($mission)) {
            debugging('The mission is no longer active.', DEBUG_DEVELOPER);
            return;
        }

        $assigners = $repository->get_assigners($mission->get_id());
        $assigner = array_reduce($assigners, function($carry, $item) use ($data) {
            if ($item->get_id() == $data->assignerid) {
                return $item;
            }
            return $carry;
        }, null);

        if (!$assigner) {
            debugging('The assigner does not (or no longer does) belong to the mission.', DEBUG_DEVELOPER);
            return;
        }

        $missionoperator->sync_assigner($mission, $assigner);
    }

}
