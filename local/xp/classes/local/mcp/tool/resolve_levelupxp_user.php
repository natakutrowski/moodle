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

use block_xp\local\group\group_policy;
use block_xp\local\permission\access_report_permissions;
use block_xp\local\sql\limit;
use block_xp\local\utils\user_utils;
use block_xp\local\xp\state_store_query;
use block_xp\local\xp\state_store_with_query;
use block_xp\local\xp\state_with_user;
use coding_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * Resolve a Level Up XP user MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resolve_levelupxp_user extends world_base {
    /**
     * Get the user resolver tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'resolve_levelupxp_user',
            'Resolve a user',
            'Find and resolve Level Up XP users by term.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ | tool_definition::RISK_PERSONAL,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the user resolver input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([
            'term' => schema::string([
                'The term to search by user first name, last name, or visible identity fields',
                'such as username, ID number, or email.',
            ])->min_length(2)->max_length(255),
        ]);
    }

    /**
     * Get the user resolver output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'user_id' => schema::integer("The Moodle user ID. Don't expose unless asked."),
                    'name' => schema::string('The user full name.'),
                    'identity' => static::get_user_identity_schema()->optional(),
                ]),
                'The list of users found for the given term, maximum 20 results.'
            ),
        ]);
    }

    /**
     * Get the visible user identity schema.
     *
     * @return schema
     */
    protected static function get_user_identity_schema(): schema {
        return schema::object([
            'username' => schema::string("The user's Moodle username.")->optional(),
            'idnumber' => schema::string("The user's Moodle ID number.")->optional(),
            'email' => schema::string("The user's email address.")->optional(),
        ], 'Visible Moodle identity fields for the user, present only when allowed by permissions and settings.');
    }

    /**
     * Check access to user report data.
     *
     * @return void
     */
    protected function permission_checks(): void {
        parent::permission_checks();

        // We require access to the report for this, because if users cannot view the report they're unlikely to
        // be able to discover which users are in this context, such as the enrolled users in the course. We do
        // not apply logs checking here, because logs can be browsed or not and reveal some users. Being able
        // to browse the logs should not give the ability to resolve users.
        $accessperms = $this->get_world()->get_access_permissions();
        if ($accessperms instanceof access_report_permissions) {
            $accessperms->require_access_report();
        }
    }

    /**
     * Find visible users matching the search term.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $context = $world->get_context();
        $store = $world->get_store();
        if (!$store instanceof state_store_with_query) {
            throw new coding_exception('State store must support querying.');
        }

        $idfields = user_utils::get_visible_identity_fields($context);
        $query = new state_store_query();
        $query->set_term($this->get_param('term'));
        $query->set_allowed_identity_fields($idfields);
        $query->set_user_filter(group_policy::from_world($world)->get_user_filter_for_any_group());
        $query->add_order_by('firstname');
        $query->add_order_by('lastname');
        $query->add_order_by('id');

        $results = [];
        foreach ($store->list($query, new limit(20)) as $state) {
            if (!$state instanceof state_with_user) {
                continue;
            }
            $user = $state->get_user();
            $result = [
                'user_id' => (int) $user->id,
                'name' => fullname($user),
            ];
            $results[] = get_levelupxp_user::add_user_identity($result, $user, $idfields);
        }
        return ['items' => $results];
    }
}
