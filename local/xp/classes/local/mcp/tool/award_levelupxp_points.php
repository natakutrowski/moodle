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
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\mcp\tool_exception;

/**
 * Award points to a user MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_levelupxp_points extends world_base {
    /**
     * Get the point award tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'award_levelupxp_points',
            'Award points to a user',
            implode(" ", [
                'Awards points to a user in a Level Up XP context.',
                'Notifying the user is optional.',
                'Notifying the user cannot be done after the fact.',
            ]),
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_WRITE | tool_definition::RISK_PERSONAL | tool_definition::RISK_COMMS,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the point award input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([
            'user_id' => schema::integer('The Moodle user ID, obtainable by calling resolve_levelupxp_user.'),
            'points' => schema::integer('The number of points to award.')->minimum(1),
            'notify' => schema::boolean([
                "Notify the user that they were awarded points.",
                "Defaults to false.",
                "Do not enable unless explicitly asked to.",
            ])->optional(),
            'message' => schema::string([
                "Optional message to include in the notification to the user.",
                "Defaults to null.",
                "Do not include a message unless explicitly asked to.",
            ])->nullable()->optional(),
        ]);
    }

    /**
     * Get the point award output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return get_levelupxp_user::get_user_state_output_schema();
    }

    /**
     * Check access to award points to the selected user.
     *
     * @return void
     */
    protected function permission_checks(): void {
        parent::permission_checks();

        // We require access to the report for this, because if users cannot view the report should not be awarding
        // points to users. This behaviour is consistent with resolve_levelupxp_user.
        $perms = $this->get_world()->get_access_permissions();
        if ($perms instanceof access_report_permissions) {
            $perms->require_access_report();
        }

        // Validate that the user id valid.
        $userid = (int) ($this->get_param('user_id'));
        if (!user_utils::is_valid_target($this->get_context(), $userid)) {
            throw new invalid_params_exception('Invalid user.');
        }

        // Validate that the feature is enabled.
        $world = $this->get_world();
        if (!world_utils::supports_local_points_management($world)) {
            throw new tool_exception('This tool is not available in this context.');
        }
    }

    /**
     * Award points and return the updated user state.
     *
     * @return array
     */
    protected function execute() {
        global $USER;

        $db = di::get('db');
        $userid = (int) ($this->get_param('user_id'));
        $points = (int) ($this->get_param('points'));
        $message = $this->get_param('message', null);
        if ($userid <= 0 || $points <= 0 || ($message !== null && !is_string($message))) {
            throw new \local_xp\local\mcp\invalid_params_exception();
        }

        $world = $this->get_world();
        $store = $world->get_store();
        $reason = \local_xp\local\reason\manual_reason::from_user($USER);
        if ($store instanceof \block_xp\local\xp\state_store_with_reason) {
            $store->increase_with_reason($userid, $points, $reason);
        } else {
            $store->increase($userid, $points);
        }

        if ((bool) ($this->get_param('notify', false))) {
            $notifier = new \local_xp\local\notification\award_notifier(\block_xp\di::get('config'), $world, $USER);
            $notifier->notify($userid, $points, $message);
        }

        $state = $world->get_store()->get_state($userid);
        $fields = \core_user\fields::for_name()
            ->with_identity($world->get_context(), false)
            ->including('id')
            ->get_sql('', false, '', '', false)
            ->selects;
        $user = $db->get_record('user', ['id' => $userid, 'deleted' => 0], $fields, IGNORE_MISSING);
        if (!$user) {
            throw new \local_xp\local\mcp\invalid_params_exception();
        }

        $idfields = user_utils::get_visible_identity_fields($world->get_context());
        return get_levelupxp_user::user_state_to_array($world, $state, $user, $idfields);
    }
}
