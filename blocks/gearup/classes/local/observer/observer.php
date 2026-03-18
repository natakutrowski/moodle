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
 * Observer.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\observer;

use block_gearup\di;
use block_gearup\local\assigner\processor\event_processor;
use block_gearup\local\repository\mission_instance_query;
use context_system;
use core\event\base;
use core\event\course_deleted;
use core\event\user_deleted;

/**
 * Observer.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Observe all events.
     *
     * @param base $event
     * @return void
     */
    public static function catch_all(base $event) {
        if ($event->is_restored()) {
            return;
        } else if (!$event->get_context()) {
            return;
        } else if (!di::get('lm')->is_active()) {
            return;
        }

        static::process_assigners($event);
        static::process_actions($event);
    }

    /**
     * Observer.
     *
     * @param course_deleted $event
     * @return void
     */
    public static function course_deleted(course_deleted $event) {
        $context = $event->get_context();
        if (!$context) {
            return;
        }

        // TODO This should be moved elsewhere.
        $missionoperator = di::get('mission_operator');
        $missions = di::get('repository')->get_missions($context);
        foreach ($missions as $mission) {
            $missionoperator->delete_mission($mission);
        }
    }

    /**
     * Observer.
     *
     * @param user_deleted $event
     * @return void
     */
    public static function user_deleted(user_deleted $event) {
        // TODO This should be moved elsewhere.
        $missionoperator = di::get('mission_operator');
        $query = (new mission_instance_query(context_system::instance()))
            ->set_subject_id($event->objectid);
        $missioninsts = di::get('repository')->get_instances_from_query($query);
        foreach ($missioninsts as $missioninst) {
            $missionoperator->delete_instance($missioninst);
        }
    }

    /**
     * Process actions.
     *
     * @param course_deleted $event
     * @return void
     */
    protected static function process_actions(base $event) {
        $actionmaker = di::get('action_maker');
        $actions = $actionmaker->make_from_event($event);
        if (empty($actions)) {
            return;
        }

        $actionprocessor = di::get('action_processor');
        foreach ($actions as $action) {
            $actionprocessor->process_action($action);
        }
    }

    /**
     * Process assigners.
     *
     * @param course_deleted $event
     * @return void
     */
    protected static function process_assigners(base $event) {
        $ap = di::get('assigner_processor');
        if ($ap instanceof event_processor) {
            $ap->process_event($event);
        }
    }

}
