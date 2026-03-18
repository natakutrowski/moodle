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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\task;

use block_gearup\di;
use block_gearup\local\mission\mission;
use block_gearup\local\repository\mission_instance_query;

/**
 * Adhoc task.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mission_recruits_delete_adhoc extends \core\task\adhoc_task {

    public function execute() {
        // Must contain missionid.
        $data = $this->get_custom_data();

        $missionid = (int) $data->missionid;
        $deletecompleted = (bool) ($data->deletedcompleted ?? false);

        try {
            static::delete_recruits($missionid, $deletecompleted);
        } catch (\moodle_exception $e) {
            if ($e->errorcode === 'notfound') {
                return; // That's OK, do nothing.
            }
            throw $e;
        }
    }

    /**
     * Delete recruits.
     *
     * This is a temporary method, it can be deleted any time, do not use!
     *
     * @param int $missionid The mission ID.
     * @param bool $deletecompleted Whether to delete completed instances.
     * @return object
     */
    public static function delete_recruits($missionid, $deletecompleted = false) {
        $repo = di::get('repository');
        $mo = di::get('mission_operator');

        $mission = $repo->get_mission($missionid);
        if (!$mission) {
            throw new \moodle_exception('notfound');
        } else if (!di::get('mission_helper')->is_active($mission)) {
            throw new \coding_exception('The mission is not active.');
        }

        $query = static::get_deletion_query($mission, $deletecompleted);

        $deletedrecruits = [];
        $deletedmis = 0;
        foreach ($repo->get_instances_from_query($query) as $mi) {
            $deletedmis++;
            $deletedrecruits[$mi->get_subject_id()] = true;
            $mo->delete_instance($mi);
        }

        return (object) [
            'ninstances' => $deletedmis,
            'nrecruits' => count($deletedrecruits),
        ];
    }

    /**
     * Get the deletion query.
     *
     * @param mission $mission
     * @param bool $deletecompleted
     * @return mission_instance_query
     */
    protected static function get_deletion_query(mission $mission, $deletecompleted = false): mission_instance_query {
        $query = new mission_instance_query($mission->get_context());
        $query->set_mission_id($mission->get_id());
        $query->set_mission_state(mission::STATE_ACTIVE);
        if (!$deletecompleted) {
            $query->filter_by_status('not_completed');
        }
        return $query;
    }

    /**
     * Process or schedule the deletion.
     *
     * This is a temporary method, it can be deleted any time, do not use!
     *
     * @param mission $mission The mission.
     * @param bool $deletecompleted Whether to delete completed instances.
     * @return object
     */
    public static function process_or_schedule_deletion(mission $mission, $deletecompleted = false) {
        $repo = di::get('repository');
        $query = static::get_deletion_query($mission, $deletecompleted);

        $ninstances = $repo->count_instances_from_query($query);
        if ($ninstances > 50) {
            $task = new static();
            $task->set_component('block_gearup');
            $task->set_custom_data(['missionid' => (int) $mission->get_id(), 'deletecompleted' => (bool) $deletecompleted]);
            \core\task\manager::queue_adhoc_task($task, true);
            return (object) ['deferred' => true];
        }

        return static::delete_recruits($mission->get_id(), $deletecompleted);
    }

}
