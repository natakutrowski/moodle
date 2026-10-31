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

use block_xp\local\utils\world_utils;
use context;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\mcp\tool_exception;

/**
 * List completion XP rules MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_completion_rules extends world_base {
    /**
     * Get the completion rules tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_completion_rules',
            'List completion rules',
            'Returns the rules that award points for completing activities, sections and courses in Level Up XP.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the completion rules input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        $schema = static::world_scoped_schema();
        if (static::is_sitewide()) {
            $schema->property('scope_course_id', schema::integer([
                'Use for specific rules scoped to a given Moodle course, thus overriding global rules.',
                'Obtainable by calling resolve_course.',
            ])->minimum(1)->optional());
        }
        return $schema;
    }

    /**
     * Get the completion rules output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'completion_target' => schema::string(
                        'Whether the rule fires on activity, section or course completion.'
                    )->enum(['activity', 'section', 'course']),
                    'awards' => schema::array_of(schema::object([
                        'rule_id' => schema::integer("Internal rule ID. Don't share with the user unless they asked."),
                        'points' => schema::integer('Points awarded when the rule matches.'),
                        'conditions' => schema::string('Human-readable filter/condition label.'),
                    ], 'The award rules ordered by priority (highest first).')),
                ]),
                'Rules that award points for the completion of activities, sections and courses.'
            ),
        ]);
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
        if (!world_utils::supports_local_points_management($world)) {
            throw new tool_exception('This tool is not available in this context.');
        }
    }

    /**
     * List the completion rules for the selected world.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $rulemanager = \block_xp\di::get('world_rule_manager_factory')->get_rule_manager($world);
        $rules = $rulemanager->get_rules($this->get_child_context());
        $rules = \block_xp\di::get('rule_sorter')->sort($rules);
        $ruledescriptor = \block_xp\di::get('rule_descriptor');

        $activityawards = [];
        $courseawards = [];
        $sectionawards = [];
        foreach ($rules as $rule) {
            $typename = $rule->get_type_name();
            if ($typename === 'cm_completion') {
                $activityawards[] = [
                    'rule_id' => $rule->get_id(),
                    'points' => (int) $rule->get_points(),
                    'conditions' => $ruledescriptor->get_conditions($rule),
                ];
            } else if ($typename === 'section_completion') {
                $sectionawards[] = [
                    'rule_id' => $rule->get_id(),
                    'points' => (int) $rule->get_points(),
                    'conditions' => $ruledescriptor->get_conditions($rule),
                ];
            } else if ($typename === 'course_completion') {
                $courseawards[] = [
                    'rule_id' => $rule->get_id(),
                    'points' => (int) $rule->get_points(),
                    'conditions' => $ruledescriptor->get_conditions($rule),
                ];
            }
        }

        return ['items' => [
            [
                'completion_target' => 'activity',
                'awards' => $activityawards,
            ],
            [
                'completion_target' => 'section',
                'awards' => $sectionawards,
            ],
            [
                'completion_target' => 'course',
                'awards' => $courseawards,
            ],
        ]];
    }

    /**
     * Get the child context.
     *
     * @return context|null
     */
    protected function get_child_context(): ?context {
        $worldcontext = $this->get_world()->get_context();
        if ($worldcontext->contextlevel != CONTEXT_SYSTEM) {
            return null;
        }

        $childcourseid = $this->get_param('scope_course_id', null);
        if (!$childcourseid) {
            return null;
        }

        $context = \context_course::instance($childcourseid, IGNORE_MISSING);
        if (!$context || !$worldcontext->is_parent_of($context, false)) {
            throw new \local_xp\local\mcp\invalid_params_exception('Invalid params: scope_course_id is not valid.');
        }

        $modinfo = get_fast_modinfo($context->instanceid);
        if (!can_access_course($modinfo->get_course(), null, '', true)) {
            throw new \local_xp\local\mcp\invalid_params_exception('Invalid params: scope_course_id is not valid.');
        }

        return $context;
    }
}
