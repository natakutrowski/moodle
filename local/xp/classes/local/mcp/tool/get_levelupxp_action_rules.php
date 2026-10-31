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
 * List action XP rules MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_levelupxp_action_rules extends world_base {
    /**
     * Get the action rules tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'get_levelupxp_action_rules',
            'List action rules',
            'Returns the rules that award points for actions taken in a Level Up XP, excluding completion-related actions.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the action rules input schema.
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
     * Get the action rules output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return schema::object([
            'items' => schema::array_of(
                schema::object([
                    'action_type' => schema::string('The type of action the rule awards points for.'),
                    'awards' => schema::array_of(schema::object([
                        'rule_id' => schema::integer("Internal rule ID. Don't share with the user unless they asked."),
                        'points' => schema::integer('Points awarded when the rule matches.'),
                        'conditions' => schema::string('Human-readable filter/condition label.'),
                        'limits' => schema::array_of(
                            schema::string(),
                            'Only present when the rule has effective limits. Plain-language limit descriptions.'
                        )->optional(),
                    ], 'The award rules ordered by priority (highest first).')),
                ]),
                'The list of rules and the actions they award points for.'
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
     * List action rules for the selected world.
     *
     * @return array
     */
    protected function execute() {
        $world = $this->get_world();
        $childcontext = $this->get_child_context();
        $rulemanager = \block_xp\di::get('world_rule_manager_factory')->get_rule_manager($world);
        $rules = $rulemanager->get_rules($childcontext);

        $completiontypes = ['cm_completion', 'course_completion', 'section_completion'];
        $rules = array_values(array_filter($rules, function ($rule) use ($completiontypes) {
            return !in_array($rule->get_type_name(), $completiontypes, true);
        }));
        $rules = \block_xp\di::get('rule_sorter')->sort($rules);

        $ruledescriptor = \block_xp\di::get('rule_descriptor');

        $byaction = [];
        foreach ($rules as $rule) {
            $actiontype = $ruledescriptor->get_type_name($rule);
            if (!isset($byaction[$actiontype])) {
                $byaction[$actiontype] = [];
            }
            $award = [
                'rule_id' => $rule->get_id(),
                'points' => (int) $rule->get_points(),
                'conditions' => $ruledescriptor->get_conditions($rule),
            ];
            $limits = self::levelupxp_get_rule_limit_descriptions($rule);
            if (!empty($limits)) {
                $award['limits'] = $limits;
            }
            $byaction[$actiontype][] = $award;
        }

        $out = [];
        foreach ($byaction as $actiontype => $awards) {
            $out[] = [
                'action_type' => $actiontype,
                'awards' => $awards,
            ];
        }

        \core_collator::asort_array_of_arrays_by_key($out, 'action_type', \core_collator::SORT_NATURAL);
        return ['items' => array_values($out)];
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

    /**
     * Get effective limit descriptions for a rule.
     *
     * @param \block_xp\local\rule\instance $rule Rule.
     * @return list<string>
     */
    protected static function levelupxp_get_rule_limit_descriptions(\block_xp\local\rule\instance $rule): array {
        $typeresolver = \block_xp\di::get('rule_type_resolver');
        $type = $typeresolver->get_type($rule->get_type_name());

        if (!$type instanceof \block_xp\local\ruletype\ruletype_with_limit) {
            return [];
        }

        $limit = $rule instanceof \local_xp\local\rule\rule_with_limit ? $rule->get_limit() : null;
        $repeatlimit = $rule instanceof \local_xp\local\rule\rule_with_limit ? $rule->get_repeat_limit() : null;

        $limit = $limit ?? $type->get_default_limit();
        $repeatlimit = $repeatlimit ?? $type->get_default_repeat_limit();

        return array_values(array_filter([
            self::levelupxp_describe_overall_limit($limit),
            self::levelupxp_describe_repeat_limit($repeatlimit, $type),
        ]));
    }

    /**
     * Describe an overall limit specification.
     *
     * @param \block_xp\local\ruletype\limit_spec $limit Limit spec.
     * @return string|null
     */
    protected static function levelupxp_describe_overall_limit(\block_xp\local\ruletype\limit_spec $limit): ?string {
        $max = $limit->get_max();
        if ($max < 1) {
            return null;
        }

        return 'At most ' . $max . ' ' . ($max === 1 ? 'award' : 'awards')
            . self::levelupxp_limit_window_phrase($limit->get_time_window(), false) . '.';
    }

    /**
     * Describe a repeat limit specification.
     *
     * @param \block_xp\local\ruletype\limit_spec $limit Limit spec.
     * @param \block_xp\local\ruletype\ruletype_with_limit $type Rule type.
     * @return string|null
     */
    protected static function levelupxp_describe_repeat_limit(
        \block_xp\local\ruletype\limit_spec $limit,
        \block_xp\local\ruletype\ruletype_with_limit $type
    ): ?string {
        $max = $limit->get_max();
        if ($max < 1 || $limit->get_scope() === \block_xp\local\ruletype\limit_spec::SCOPE_NONE) {
            return null;
        }

        $label = self::levelupxp_get_repeat_scope_label($limit, $type);
        if ($label !== '') {
            return $label . self::levelupxp_limit_window_phrase($limit->get_time_window(), true) . '.';
        }

        return 'At most ' . $max . ' ' . ($max === 1 ? 'award' : 'awards')
            . ' for the same action' . self::levelupxp_limit_window_phrase($limit->get_time_window(), true) . '.';
    }

    /**
     * Get a phrase for a limit window.
     *
     * @param int $window Window constant.
     * @param bool $repeat Whether this is a repeat/duplicate limit.
     * @return string
     */
    protected static function levelupxp_limit_window_phrase(int $window, bool $repeat): string {
        if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_NONE) {
            return $repeat ? ' ever' : ' in total';
        } else if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_1H) {
            return ' per rolling hour';
        } else if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_HOURLY) {
            return ' per hour';
        } else if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_DAILY) {
            return ' per day';
        } else if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_WEEKLY) {
            return ' per week';
        } else if ($window === \block_xp\local\ruletype\limit_spec::WINDOW_MONTHLY) {
            return ' per month';
        }

        return '';
    }

    /**
     * Get the user-facing repeat scope label.
     *
     * @param \block_xp\local\ruletype\limit_spec $limit Limit spec.
     * @param \block_xp\local\ruletype\ruletype_with_limit $type Rule type.
     * @return string
     */
    protected static function levelupxp_get_repeat_scope_label(
        \block_xp\local\ruletype\limit_spec $limit,
        \block_xp\local\ruletype\ruletype_with_limit $type
    ): string {
        foreach ($type->get_repeat_limit_options() as $option) {
            if ($option->get_value() === $limit->get_scope()) {
                return $option->get_once_label();
            }
        }
        return '';
    }
}
