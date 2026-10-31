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

namespace local_xp\local\mcp\tool;

use block_xp\local\utils\text_utils;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * List Level Up XP levels MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_levels extends world_base {
    /**
     * Get the levels tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_levels',
            'Get the levels',
            'Get the list of all levels (number, points, names, ...) in a Level Up XP context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the levels input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema();
    }

    /**
     * Get the levels output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(schema::object([
                'level' => schema::integer('The level number.'),
                'name' => schema::string('The name given to the level, if any.')->nullable(),
                'description' => schema::string('The description of the level, if any.')->nullable(),
                'total_points_required' => schema::integer(
                    'The total number of points required for a user to obtain to reach this level.'
                ),
                'points_to_next_level' => schema::integer(
                    'The additional number of points required to reach the next level, or null for the last level.'
                )->nullable(),
                'notification_message_when_level_reached' => schema::string(
                    'Message shown in the level-up notification when the user attains the level, or null.'
                )->nullable()->optional(),
                'badge_to_award_when_level_reached' => schema::object([
                    'badge_id' => schema::integer(
                        "Internal Moodle badge ID. Don't share the ID with the user unless they asked."
                    ),
                    'badge_name' => schema::string('The badge name.'),
                ], 'The Moodle badge to award when the user attains the level, or null.')->nullable()->optional(),
            ])),
        ]);
    }

    /**
     * List the levels for the selected world.
     *
     * @return array
     */
    protected function execute() {
        global $USER;
        $world = $this->get_world();
        $levelsinfo = $world->get_levels_info();
        $count = $levelsinfo->get_count();

        $badgemap = [];
        $badgemanager = \block_xp\di::get('badge_manager');
        foreach ($badgemanager->get_compatible_badges($world->get_context(), $USER->id) as $b) {
            $badgemap[(int) $b->id] = $b->name;
        }

        $results = [];
        for ($i = 1; $i <= $count; $i++) {
            $levelobj = $levelsinfo->get_level($i);
            $name = null;
            if ($levelobj instanceof \block_xp\local\xp\level_with_name) {
                $name = text_utils::trim_or_null($levelobj->get_name());
            }
            $description = null;
            if ($levelobj instanceof \block_xp\local\xp\level_with_description) {
                $description = text_utils::trim_or_null($levelobj->get_description());
            }
            $notification = null;
            if ($levelobj instanceof \local_xp\local\xp\level_with_popup_message) {
                $msg = $levelobj->get_popup_message();
                if ($msg !== null) {
                    $notification = text_utils::trim_or_null($msg);
                }
            }
            $badgetoaward = null;
            if ($levelobj instanceof \local_xp\local\xp\level_with_badge_award) {
                $bid = $levelobj->get_badge_award_id();
                if ($bid) {
                    $badgetoaward = [
                        'badge_id' => (int) $bid,
                        'badge_name' => isset($badgemap[$bid])
                            ? (string) $badgemap[$bid]
                            : get_string('unknownbadgea', 'block_xp', $bid),
                    ];
                }
            }
            $pointstonext = null;
            if ($i < $count) {
                $next = $levelsinfo->get_level($i + 1);
                $pointstonext = (int) ($next->get_xp_required() - $levelobj->get_xp_required());
            }
            $result = [
                'level' => (int) $levelobj->get_level(),
                'name' => $name,
                'description' => $description,
                'total_points_required' => (int) $levelobj->get_xp_required(),
                'points_to_next_level' => $pointstonext,
            ];
            if (!empty($notification)) {
                $result['notification_message_when_level_reached'] = $notification;
            }
            if (!empty($badgetoaward)) {
                $result['badge_to_award_when_level_reached'] = $badgetoaward;
            }
            $results[] = $result;
        }

        return ['items' => $results];
    }
}
