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

use core\context;
use core\context\system;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;

/**
 * Resolve courses.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resolve_course extends base {
    /**
     * Get the course resolver tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'resolve_course',
            'Resolve courses to use with Level Up XP tools.',
            'Find and resolve courses that can be used with Level Up XP tools.',
            [CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the course resolver input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        return schema::object([
            'term' => schema::string('The term to search by (course shortname or fullname).')
                ->min_length(2)
                ->max_length(255),
        ]);
    }

    /**
     * Get the course resolver output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'course_id' => schema::integer('The course ID.'),
                    'course_shortname' => schema::string('The course shortname.'),
                    'course_fullname' => schema::string('The course fullname.'),
                ]),
                'The list of courses found for the given term, maximum 25 results.'
            ),
        ]);
    }

    /**
     * Resolve the system context.
     *
     * @return context
     */
    protected function resolve_context(): context {
        return system::instance();
    }

    /**
     * Allow course searches for every user.
     *
     * @return void
     */
    protected function permission_checks(): void {
        // Anyone can use this because we filter the visibility of courses using the core permissions.
    }

    /**
     * Find visible courses matching the search term.
     *
     * @return array
     */
    protected function execute() {
        $courses = \block_xp\external\search_courses::execute((string) $this->get_param('term'));
        $results = [];
        foreach ($courses as $course) {
            $results[] = [
                'course_id' => (int) $course['id'],
                'course_shortname' => $course['shortname'],
                'course_fullname' => $course['fullname'],
            ];
            if (count($results) >= 25) {
                break;
            }
        }
        return ['items' => $results];
    }
}
