<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\external;

use block_xp\di;
use block_xp\external\external_description;
use block_xp\external\external_function_parameters;
use block_xp\external\external_single_structure;
use block_xp\external\external_value;
use block_xp\local\utils\world_utils;
use local_xp\local\config\default_course_world_config;

/**
 * External function.
 *
 * @package    local_xp
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_setup extends external_api {
    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, '', VALUE_DEFAULT),
            'contextid' => new external_value(PARAM_INT, '', VALUE_DEFAULT),
        ]);
    }

    /**
     * External function.
     *
     * @param ?int $courseid The course ID.
     * @param ?int $contextid The context ID.
     * @return object
     */
    public static function execute($courseid = null, $contextid = null) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'contextid'));
        extract($params); // @codingStandardsIgnoreLine

        $adminconfig = di::get('config');
        $currencyfactory = di::get('currency_factory');
        $bifinder = di::get('course_world_block_any_instance_finder_in_context');

        if (!empty($contextid)) {
            $world = di::get('context_world_factory')->get_world_from_context(\context::instance_by_id($contextid));
        } else {
            $world = di::get('course_world_factory')->get_world($courseid ?? 0);
        }
        $context = $world->get_context();
        self::validate_context($context);
        di::get('addon')->require_activated();

        // Find the block instance, we use the course context because the front page is a course.
        $perms = $world->get_access_permissions();
        $publicdata = [
            'contextmode' => $adminconfig->get('context'),
            'perms' => [
                'canaccess' => $perms->can_access(),
                'canmanage' => $perms->can_manage(),
            ],
            'block' => [
                'visible' => false,
            ],
        ];

        if (!$perms->can_access() || isguestuser() || !$USER->id) {
            // Early bail if the user cannot see anything.
            return $publicdata;
        } else if (!$bifinder->has_instance_in_context('xp', $context)) {
            // We could not find the block, so let's skip it.
            return $publicdata;
        }

        $config = $world->get_config();
        $currency = $currencyfactory->get_currency_for_world($world);

        // Description.
        $prefname = 'block_xp_block_intro_' . $context->id;
        $description = '';
        if ($perms->can_manage() || !get_user_preferences($prefname, false)) {
            $description = self::format_string($config->get('blockdescription'), $context);
        }

        // Phew, we're done. Note that this is NOT a recursive merge.
        return [
            'block' => [
                'title' => self::format_string($config->get('blocktitle'), $context),
                'description' => $description,
                'recentactivity' => world_utils::supports_recent_activity($world) ? $config->get('blockrecentactivity') : 0,
                // TODO Implement better visibility check. E.g. we should look at the block_position instead, if any.
                'visible' => true,
            ],
            'config' => [
                'enableinfos' => $config->get('enableinfos'),
                'enableladder' => $config->get('enableladder'),
                'enablegroupladder' => world_utils::supports_team_leaderboard($world)
                    && $config->get('enablegroupladder') != default_course_world_config::GROUP_LADDER_NONE,
            ],
            'visuals' => [
                'currency' => self::serialize_currency($currency),
            ],
        ] + $publicdata;
    }

    /**
     * External function return definition.
     *
     * @return external_description
     */
    public static function execute_returns() {
        return new external_single_structure([
            'contextmode' => new external_value(PARAM_INT),
            'perms' => new external_single_structure([
                'canaccess' => new external_value(PARAM_BOOL),
                'canmanage' => new external_value(PARAM_BOOL),
            ]),
            'block' => new external_single_structure([
                'title' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'description' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                'recentactivity' => new external_value(PARAM_INT, 0, VALUE_OPTIONAL),
                'visible' => new external_value(PARAM_BOOL),
            ]),
            'config' => new external_single_structure([
                'enableinfos' => new external_value(PARAM_BOOL),
                'enableladder' => new external_value(PARAM_BOOL),
                'enablegroupladder' => new external_value(PARAM_INT),
            ], '', VALUE_OPTIONAL),
            'visuals' => new external_single_structure([
                'currency' => self::currency_description(),
            ], '', VALUE_OPTIONAL),
        ]);
    }
}
