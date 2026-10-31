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
use context_system;
use core\context;
use core\context\system;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * Resolve the context of Level Up XP MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resolve_levelupxp_context extends base {
    /**
     * Get the Level Up XP context resolver definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'resolve_levelupxp_context',
            'Resolve the context of Level Up XP',
            'Find a Level Up XP context by course shortname or fullname, or resolve the overall context.'
                . 'Results exclude unavailable contexts, or contexts without sufficient permissions.',
            [CONTEXT_COURSE],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the context resolver input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return schema::object([
            'type' => schema::string('The type of context to resolve (course or overall). Defaults to course.')
                ->enum(['course', 'overall'])->optional(),
            'term' => schema::string([
                'The course shortname or fullname to search for.',
                'Required for course type. Unused for overall.',
            ])->min_length(2)->max_length(255)->optional(),
        ]);
    }

    /**
     * Get the context resolver output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'context_id' => schema::integer("The Level Up XP context. Don't share the ID with the user unless they asked."),
                    'course_shortname' => schema::string('The course shortname.')->optional(),
                    'course_fullname' => schema::string('The course fullname.')->optional(),
                ]),
                'The matching contexts, maximum 20 results. Empty when no accessible context is available.'
            ),
        ]);
    }

    /**
     * Resolve the system discovery context.
     *
     * @return context
     */
    protected function resolve_context(): context {
        // As this endpoint is used to discover contexts, the effective context is system, and users
        // are expected to be given the local/xp:usemcp capability at the system context.
        return system::instance();
    }

    /**
     * Allow context discovery before filtering results.
     *
     * @return void
     */
    protected function permission_checks(): void {
        // Discovery filters results by access, manage permissions and the ability to use MCP in each context.
    }

    /**
     * Find accessible Level Up XP contexts.
     *
     * @return array
     */
    protected function execute() {
        global $CFG;

        if ($this->get_param('type', 'course') === 'overall') {
            $sysctx = context_system::instance();
            if (
                static::is_sitewide()
                    || !di::get('config')->get('enableglobalworld')
                    || !has_capability('block/xp:manage', $sysctx)
                    || !has_capability('local/xp:usemcp', $sysctx)
            ) {
                return ['items' => []];
            }
            return ['items' => [['context_id' => $sysctx->id]]];
        }

        $term = $this->get_param('term', null);
        if ($term === null) {
            throw new invalid_params_exception('A term is required when resolving from course type.');
        }

        require_once($CFG->dirroot . '/course/externallib.php');
        $courses = \core_course_external::search_courses(
            'search',
            $term,
            0,
            20,
            ['block/xp:manage', 'local/xp:usemcp']
        )['courses'];

        $results = [];
        foreach ($courses as $course) {
            $results[] = [
                'context_id' => \context_course::instance($course['id'])->id,
                'course_shortname' => $course['shortname'],
                'course_fullname' => $course['fullname'],
            ];
        }
        return ['items' => $results];
    }
}
