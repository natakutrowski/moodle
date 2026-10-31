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
use block_xp\local\sql\limit;
use block_xp\local\utils\world_utils;
use block_xp\local\world\world_with_features;
use block_xp\local\xp\state_with_subject;
use local_xp\local\config\default_course_world_config;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\mcp\tool_exception;
use local_xp\local\utils\agent_utils;

/**
 * Get the team leaderboard MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_team_leaderboard extends world_base {
    /**
     * Get the team leaderboard tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_team_leaderboard',
            'Get the team leaderboard',
            'Returns a paginated leaderboard of teams for a Level Up XP context.',
            [CONTEXT_SYSTEM, CONTEXT_COURSE],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the team leaderboard input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema([
            'page' => schema::integer('Page number. Defaults to 1.')->minimum(1)->optional(),
            'perpage' => schema::integer('Number of entries per page. Defaults to 20 and is capped at 100.')
                ->minimum(1)->maximum(100)->optional(),
        ]);
    }

    /**
     * Get the team leaderboard output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return agent_utils::paginated_results_schema(schema::object([
            'rank' => schema::integer('The team rank.'),
            'team_id' => schema::integer("The team ID. Don't expose unless asked."),
            'name' => schema::string('The team name. Abides by the anonymity settings.'),
            'points' => schema::integer(
                'The team total XP points. Omitted when the XP column is hidden as per settings.'
            )->optional(),
        ]));
    }

    /**
     * Check permissions.
     *
     * @return void
     */
    protected function permission_checks(): void {
        parent::permission_checks();

        // Validate that the feature is enabled.
        $world = $this->get_world();
        if (!world_utils::supports_team_leaderboard($world)) {
            throw new tool_exception('This tool is not available in this context.');
        }
    }

    /**
     * Get a page of the team leaderboard.
     *
     * @return array
     */
    protected function execute() {
        $page = max(1, (int) ($this->get_param('page', 1)));
        $perpage = max(1, min(100, (int) ($this->get_param('perpage', 20))));
        $world = $this->get_world();
        $config = $world->get_config();
        if ((int) $config->get('enablegroupladder') === default_course_world_config::GROUP_LADDER_NONE) {
            throw new invalid_params_exception('The team leaderboard is not enabled in this context.');
        }
        $additionalcols = explode(',', (string) $config->get('groupladdercols'));
        $canseepoints = in_array('xp', $additionalcols, true);

        $leaderboard = di::get('course_world_grouped_leaderboard_factory')->get_course_grouped_leaderboard($world);
        $total = (int) $leaderboard->get_count();
        $totalpages = max(1, (int) ceil($total / $perpage));
        $page = min($page, $totalpages);
        $offset = ($page - 1) * $perpage;
        $ranking = $leaderboard->get_ranking(new limit($perpage, $offset));

        $results = [];
        foreach ($ranking as $rank) {
            $state = $rank->get_state();
            $entry = [
                'rank' => (int) $rank->get_rank(),
                'team_id' => (int) $state->get_id(),
                'name' => $state instanceof state_with_subject ? $state->get_name() : '?',
            ];
            if ($canseepoints) {
                $entry['points'] = (int) $state->get_xp();
            }
            $results[] = $entry;
        }

        return [
            'current_page' => $page,
            'next_page' => $page < $totalpages ? $page + 1 : null,
            'total_pages' => $totalpages,
            'items' => $results,
        ];
    }
}
