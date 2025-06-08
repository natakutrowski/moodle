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
 * Services.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_gearup_accept_mission' => [
        'classname' => 'block_gearup\external\accept_mission',
        'methodname' => 'execute',
        'description' => 'Accept a mission',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_finish_mission' => [
        'classname' => 'block_gearup\external\finish_mission',
        'methodname' => 'execute',
        'description' => 'Finish a mission',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_assigner_types' => [
        'classname' => 'block_gearup\external\get_assigner_types',
        'methodname' => 'execute',
        'description' => 'Get the list of assigner types',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_mission' => [
        'classname' => 'block_gearup\external\get_mission',
        'methodname' => 'execute',
        'description' => 'Get a mission',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_mission_instance' => [
        'classname' => 'block_gearup\external\get_mission_instance',
        'methodname' => 'execute',
        'description' => 'Get a mission instance',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_mission_instance_page' => [
        'classname' => 'block_gearup\external\get_mission_instance_page',
        'methodname' => 'execute',
        'description' => 'Get a mission instance',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_objective_types' => [
        'classname' => 'block_gearup\external\get_objective_types',
        'methodname' => 'execute',
        'description' => 'Get the list of objective types',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_outcome_types' => [
        'classname' => 'block_gearup\external\get_outcome_types',
        'methodname' => 'execute',
        'description' => 'Get the list of outcome types',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_get_tracker' => [
        'classname' => 'block_gearup\external\get_tracker',
        'methodname' => 'execute',
        'description' => 'Get the tracker',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_mark_achievement_unlocked_notification_seen' => [
        'classname' => 'block_gearup\external\mark_achievement_unlocked_notification_seen',
        'methodname' => 'execute',
        'description' => 'Mark an achievement unlocked notification as seen',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
        'deprecated' => true,
    ],
    'block_gearup_mark_mission_seen' => [
        'classname' => 'block_gearup\external\mark_mission_seen',
        'methodname' => 'execute',
        'description' => 'Mark a mission as seen',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_search_users' => [
        'classname' => 'block_gearup\external\search_users',
        'methodname' => 'execute',
        'description' => 'Search for users',
        'type' => 'read',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_time_watched' => [
        'classname' => 'block_gearup\external\time_watched',
        'methodname' => 'execute',
        'description' => 'Report time watching content',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
    'block_gearup_video_watched' => [
        'classname' => 'block_gearup\external\video_watched',
        'methodname' => 'execute',
        'description' => 'Report a video as having been watched',
        'type' => 'write',
        'ajax' => true,
        'services' => [],
    ],
];
