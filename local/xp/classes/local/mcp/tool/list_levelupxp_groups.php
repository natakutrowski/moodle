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
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\utils\agent_utils;

/**
 * List Level Up XP groups MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_levelupxp_groups extends world_base {
    /** @var int Page size to use when pagination is needed. */
    protected const PAGE_SIZE = 100;

    /**
     * Get the group list tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'list_levelupxp_groups',
            'List groups to be used with Level Up XP tools.',
            'List groups that can be used to filter in the Level Up XP context.',
            [CONTEXT_COURSE],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the group list input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([
            'page' => schema::integer('Page number, starting at 1.')->minimum(1)->optional(),
        ]);
    }

    /**
     * Get the group list output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return agent_utils::paginated_results_schema(schema::object([
            'group_id' => schema::integer('The Moodle group ID, or 0 for special cases.'),
            'name' => schema::string('The group name.'),
        ]));
    }

    /**
     * List selectable groups in the selected world.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $grouppolicy = group_policy::from_world($world);
        $groups = array_map(function ($group) {
            return [
                'group_id' => $group->id,
                'name' => $group->name,
            ];
        }, $grouppolicy->get_selectable_groups());
        return static::paginate_groups($groups, (int) $this->get_param('page', 1));
    }

    /**
     * Paginate group output only when the group list is large.
     *
     * @param array $groups Groups.
     * @param int $requestedpage Requested page.
     * @return array<string, mixed>
     */
    protected static function paginate_groups(array $groups, int $requestedpage): array {
        $total = count($groups);
        if ($total <= static::PAGE_SIZE) {
            return [
                'current_page' => 1,
                'next_page' => null,
                'total_pages' => 1,
                'items' => $groups,
            ];
        }

        $page = max(1, $requestedpage);
        $totalpages = (int) ceil($total / static::PAGE_SIZE);
        $page = min($page, $totalpages);
        $offset = ($page - 1) * static::PAGE_SIZE;

        return [
            'current_page' => $page,
            'next_page' => $page < $totalpages ? $page + 1 : null,
            'total_pages' => $totalpages,
            'items' => array_slice($groups, $offset, static::PAGE_SIZE),
        ];
    }
}
