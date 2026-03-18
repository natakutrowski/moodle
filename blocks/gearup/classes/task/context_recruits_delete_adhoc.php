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
use context;

/**
 * Adhoc task.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_recruits_delete_adhoc extends \core\task\adhoc_task {

    public function execute() {
        // Must contain contextid.
        $data = $this->get_custom_data();

        $contextid = (int) $data->contextid;
        static::delete_recruits($contextid);
    }

    /**
     * Delete recruits.
     *
     * This is a temporary method, it can be deleted any time, do not use!
     *
     * @param int $contextid The context ID.
     * @return object
     */
    public static function delete_recruits(int $contextid) {
        $repo = di::get('repository');
        $mo = di::get('mission_operator');
        $mh = di::get('mission_helper');

        $context = context::instance_by_id($contextid);
        $query = static::get_deletion_query($context);

        $deletedrecruits = [];
        $deletedmis = 0;
        foreach ($repo->get_instances_from_query($query) as $mi) {
            if (!$mh->is_active($mi)) {
                continue;
            }

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
     * @param context $context The context.
     * @return mission_instance_query
     */
    protected static function get_deletion_query(context $context): mission_instance_query {
        $query = (new mission_instance_query($context))
            ->set_context_id($context->id)
            ->set_mission_state(mission::STATE_ACTIVE);
        return $query;
    }

    /**
     * Process or schedule the deletion.
     *
     * This is a temporary method, it can be deleted any time, do not use!
     *
     * @param context $context The context.
     * @return object
     */
    public static function process_or_schedule_deletion(context $context) {
        $repo = di::get('repository');
        $query = static::get_deletion_query($context);

        $ninstances = $repo->count_instances_from_query($query);
        if ($ninstances > 50) {
            $task = new static();
            $task->set_component('block_gearup');
            $task->set_custom_data(['contextid' => (int) $context->id]);
            \core\task\manager::queue_adhoc_task($task, true);
            return (object) ['deferred' => true];
        }

        return static::delete_recruits($context->id);
    }

}
