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

use block_xp\local\division\group_division;
use block_xp\local\group\group_policy;
use context_course;
use local_xp\local\config\default_course_world_config;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * Get the leaderboard MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_leaderboard extends world_base {
    /**
     * Get the leaderboard tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_leaderboard',
            'Get the leaderboard',
            'Returns a paginated leaderboard for a Level Up XP context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ | tool_definition::RISK_PERSONAL,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the leaderboard input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return static::world_scoped_schema(array_filter([
            'group_id' => !static::is_sitewide() ? schema::integer([
                'Group ID to filter the participants, obtainable by calling list_levelupxp_groups.',
                'Leave empty for default selection.',
                'Only usable when leaderboard is isolated per group.',
            ])->minimum(0)->optional() : null,
            'page' => schema::integer('Page number. Defaults to 1.')->minimum(1)->optional(),
            'perpage' => schema::integer('Number of entries per page. Defaults to 20 and is capped at 100.')
                ->minimum(1)->maximum(100)->optional(),
        ]));
    }

    /**
     * Get the leaderboard output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'rank' => schema::raw([
                        'type' => ['number', 'string'],
                    ])->description([
                        'The participant rank. Can be absolute (1, 2, 3), relative in points (+123, -456, 0),',
                        'or omitted as per settings.',
                    ])->optional(),
                    'user_id' => schema::integer([
                        'The Moodle user ID of the participant. Not applicable when anonymity is enabled.',
                        'Don\'t expose unless asked.',
                    ]),
                    'name' => schema::string('The participant name. Abides by the anonymity settings.'),
                    'points' => schema::integer(
                        'The participant total XP points, if column is visible as per settings.'
                    )->optional(),
                    'level' => schema::integer('The participant level number.'),
                ]),
                'Leaderboard entries for the requested page.'
            ),
        ]);
    }

    /**
     * Get a page of the individual leaderboard.
     *
     * @return array
     */
    protected function execute() {
        $page = max(1, (int) ($this->get_param('page', 1)));
        $perpage = max(1, min(100, (int) ($this->get_param('perpage', 20))));
        $offset = ($page - 1) * $perpage;
        $requestedgroupid = $this->get_param('group_id', null);

        $world = $this->get_world();
        $config = $world->get_config();
        $additionalcols = explode(',', (string) $config->get('laddercols'));
        $canseepoints = in_array('xp', $additionalcols, true);
        $canseerank = $config->get('rankmode') != \block_xp\local\config\course_world_config::RANK_OFF;
        $isrelrank = $config->get('rankmode') == \block_xp\local\config\course_world_config::RANK_REL;

        $isdefaultiso = (int) $config->get('ladderiso') === default_course_world_config::LEADERBOARD_ISO_DEFAULT;
        $lbf = \block_xp\di::get('leaderboard_factory_maker')->get_leaderboard_factory($world);

        // Special case for viewing the group division.
        if ($requestedgroupid !== null) {
            if (!$isdefaultiso) {
                throw new invalid_params_exception('Invalid params: group_id cannot be used with the leaderboard isolation.');
            }
            $grouppolicy = group_policy::from_world($world);
            if (!$grouppolicy->can_select_group($requestedgroupid)) {
                throw new invalid_params_exception('Invalid params: group_id is invalid.');
            }
            $division = new group_division($requestedgroupid);
            $leaderboard = $lbf->get_leaderboard_for_division($division);
        } else {
            // Default behaviour, which will infer the division from the user.
            $leaderboard = $lbf->get_leaderboard();
        }

        $ranking = $leaderboard->get_ranking(new \block_xp\local\sql\limit($perpage, $offset));
        $results = [];
        foreach ($ranking as $rank) {
            $state = $rank->get_state();
            $entry = [
                'user_id' => (int) $state->get_id(),
                'name' => $state instanceof \block_xp\local\xp\state_with_subject ? $state->get_name() : '?',
                'level' => $state->get_level()->get_level(),
            ];
            if ($canseerank) {
                $rankn = $rank->get_rank();
                $entry['rank'] = $isrelrank ? (string) ($rankn > 0 ? '+' . $rankn : $rankn) : $rankn;
            }
            if ($canseepoints) {
                $entry['points'] = (int) $state->get_xp();
            }
            $results[] = $entry;
        }
        return ['items' => $results];
    }
}
