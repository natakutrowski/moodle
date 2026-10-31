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
use core\exception\coding_exception;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\utils\agent_utils;

/**
 * List Level Up XP user states MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_levelupxp_user_states extends world_base {
    /**
     * Get the user state report tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'list_levelupxp_user_states',
            'List users states (report)',
            'Returns a report of users and their Level Up XP points, level, rank, etc.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ | tool_definition::RISK_PERSONAL,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the user state report input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema(array_filter([
            'group_id' => !static::is_sitewide()
                ? schema::integer([
                    'When set to a Moodle group ID, only users of that group are returned.',
                    'Obtainable by calling list_levelupxp_groups.',
                ])->minimum(0)->optional()
                : null,
            'order_by' => schema::string('Sort order. Defaults to points_desc.')
                ->enum(['points_desc', 'points_asc', 'name_asc', 'name_desc'])
                ->optional(),
            'page' => schema::integer('Page number, starting at 1. Defaults to 1.')->minimum(1)->optional(),
            'perpage' => schema::integer('Number of users per page. Defaults to 20 and is capped at 100.')
                ->minimum(1)->maximum(100)->optional(),
        ]));
    }

    /**
     * Get the user state report output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return agent_utils::paginated_results_schema(get_levelupxp_user::get_user_state_output_schema());
    }

    /**
     * Check access to the user state report.
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
     * Get a page of user states from the report.
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
        $orderby = (string) ($this->get_param('order_by', 'points_desc'));
        $page = max(1, (int) ($this->get_param('page', 1)));
        $perpage = max(1, min(100, (int) ($this->get_param('perpage', 20))));

        // Defaults to all participants, and validate that it is a valid choice.
        $groupid = $this->get_param('group_id', 0);
        $grouppolicy = group_policy::from_world($world);
        if (!$grouppolicy->can_select_group($groupid)) {
            throw new invalid_params_exception('Invalid params: group_id was invalid or not provided.');
        }

        $query = new state_store_query();
        if ($groupid) {
            $query->set_user_filter($grouppolicy->get_user_filter($groupid));
        }

        // Ordering options.
        $orders = [
            'points_desc' => [['xp', SORT_DESC]],
            'points_asc' => [['xp', SORT_ASC]],
            'name_asc' => [['firstname', SORT_ASC], ['lastname', SORT_ASC]],
            'name_desc' => [['firstname', SORT_DESC], ['lastname', SORT_DESC]],
        ];
        if (!isset($orders[$orderby])) {
            throw new invalid_params_exception('Invalid params: unsupported order_by.');
        }
        foreach ($orders[$orderby] as [$field, $direction]) {
            $query->add_order_by($field, $direction);
        }

        $total = $store->count($query);
        $totalpages = max(1, (int) ceil($total / $perpage));
        $page = min($page, $totalpages);
        $offset = ($page - 1) * $perpage;

        $idfields = user_utils::get_visible_identity_fields($context);
        $items = [];
        foreach ($store->list($query, new limit($perpage, $offset)) as $state) {
            if (!$state instanceof state_with_user) {
                continue;
            }
            $items[] = get_levelupxp_user::user_state_to_array($world, $state, $state->get_user(), $idfields);
        }

        return [
            'current_page' => $page,
            'next_page' => $page < $totalpages ? $page + 1 : null,
            'total_pages' => $totalpages,
            'items' => $items,
        ];
    }
}
