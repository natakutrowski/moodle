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

use block_xp\di;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\utils\user_utils;
use block_xp\local\utils\world_utils;
use coding_exception;
use local_xp\local\config\default_course_world_config;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * Get a Level Up XP user state MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_user extends world_base {
    /**
     * Get the user state tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_user',
            'Get a user\'s state',
            'Returns points, level and rank of a user in Level Up XP for a context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ | tool_definition::RISK_PERSONAL,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the user state input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([
            'user_id' => schema::integer('The Moodle user ID, obtainable by calling resolve_levelupxp_user.')
                ->minimum(1),
        ]);
    }

    /**
     * Get the user state output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return static::get_user_state_output_schema();
    }

    /**
     * Check access.
     *
     * @return void
     */
    protected function permission_checks(): void {
        $accessperms = $this->get_world()->get_access_permissions();
        if (!$accessperms instanceof access_report_permissions) {
            throw new coding_exception('Access permissions object requires report permissions.');
        }
        $accessperms->require_access_report();
    }

    /**
     * Get the reusable user state output schema.
     *
     * @return schema
     */
    public static function get_user_state_output_schema(): schema {
        return schema::object([
            'user_id' => schema::integer('The user ID.'),
            'name' => schema::string('The full name of the user.'),
            'identity' => schema::object([
                'username' => schema::string("The user's Moodle username.")->optional(),
                'idnumber' => schema::string("The user's Moodle ID number.")->optional(),
                'email' => schema::string("The user's email address.")->optional(),
            ], [
                "Visible Moodle identity fields for the user,",
                "present only when allowed by Moodle permissions and settings.",
            ])->optional(),
            'points' => schema::integer("The user's total XP points."),
            'level' => schema::integer("The user's current level."),
            'next_level' => schema::integer('The next level, or null when already at max level.')->nullable(),
            'points_until_next_level' => schema::integer('The number of points remaining before the next level.'),
            'percentage_towards_next_level' => schema::number('Percentage of progression in the current level, 100 at max level.'),
            'teams' => schema::array_of(schema::object([
                'team_id' => schema::integer("The team ID. Don't expose unless asked."),
                'name' => schema::string('The team name.'),
            ]), 'The teams the user belongs to in the applicable, if enabled.')
            ->optional(),
        ]);
    }

    /**
     * Get the state of the selected user.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $context = $world->get_context();
        $store = $world->get_store();
        $userid = $this->get_param('user_id');
        $db = di::get('db');

        try {
            $state = $store->get_state($userid);
        } catch (\Exception $e) {
            throw new \local_xp\local\mcp\invalid_params_exception('Invalid user.');
        }

        if (!user_utils::is_valid_target($context, $userid)) {
            throw new \local_xp\local\mcp\invalid_params_exception('Invalid user.');
        }

        $idfields = user_utils::get_visible_identity_fields($context);
        $sqlidfields = \core_user\fields::for_name()
            ->with_identity($context, false)
            ->including('id', 'suspended')
            ->get_sql('', false, '', '', false)->selects;

        $user = $db->get_record(
            'user',
            ['id' => $userid, 'deleted' => 0],
            $sqlidfields,
            IGNORE_MISSING
        );
        if (!$user) {
            throw new \local_xp\local\mcp\invalid_params_exception('Invalid user.');
        }

        return static::user_state_to_array($world, $state, $user, $idfields);
    }

    /**
     * Format a user state for MCP output.
     *
     * @param \block_xp\local\world $world World.
     * @param \block_xp\local\xp\user_state $state User state.
     * @param \stdClass $user User record.
     * @param array $idfields Identity fields.
     * @return array<string, mixed>
     */
    public static function user_state_to_array(
        \block_xp\local\world $world,
        \block_xp\local\xp\user_state $state,
        \stdClass $user,
        array $idfields
    ): array {
        $userid = (int) $user->id;
        $level = $state->get_level()->get_level();
        $points = (int) $state->get_xp();
        $levelsinfo = $world->get_levels_info();
        $config = $world->get_config();
        $nextlevel = null;
        $pointsuntilnextlevel = 0;
        $percentage = 100.0;
        $teamleaderboardenabled = world_utils::supports_team_leaderboard($world)
            && $world instanceof \block_xp\local\course_world
            && (int) $config->get('enablegroupladder') !== \local_xp\local\config\default_course_world_config::GROUP_LADDER_NONE;

        if ($level < $levelsinfo->get_count()) {
            $next = $levelsinfo->get_level($level + 1);
            $nextlevel = $next->get_level();
            $pointsuntilnextlevel = max(0, (int) ($next->get_xp_required() - $points));
            $percentage = max(0.0, min(100.0, $state->get_ratio_in_level() * 100));
        }

        $name = $state instanceof \block_xp\local\xp\state_with_subject ? $state->get_name() : fullname($user);
        if (!empty($user->suspended)) {
            $name .= ' (' . get_string('suspended', 'core') . ')';
        }

        $out = [
            'user_id' => $userid,
            'name' => $name,
            'points' => $points,
            'level' => $level,
            'next_level' => $nextlevel,
            'points_until_next_level' => $pointsuntilnextlevel,
            'percentage_towards_next_level' => (float) format_float($percentage, 1, false),
        ];

        if ($teamleaderboardenabled) {
            $out['teams'] = [];
            $teamresolver = \block_xp\di::get('course_world_grouped_leaderboard_factory')
                ->get_course_team_membership_resolver($world);
            foreach ($teamresolver->get_teams_of_member($userid) as $team) {
                $out['teams'][] = [
                    'team_id' => (int) $team->get_id(),
                    'name' => $team->get_name(),
                ];
            }
        }

        return self::add_user_identity($out, $user, $idfields);
    }

    /**
     * Add visible identity details to a user response.
     *
     * @param array<string, mixed> $out Response.
     * @param \stdClass $user User record.
     * @param array $idfields Identity fields.
     * @return array<string, mixed>
     */
    public static function add_user_identity(array $out, \stdClass $user, array $idfields): array {
        $identity = [];
        foreach ($idfields as $field) {
            $identity[$field] = (string) ($user->{$field} ?? '');
        }

        if (!empty($identity)) {
            $out['identity'] = $identity;
        }

        return $out;
    }
}
