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
use block_xp\local\group\group_policy;
use block_xp\local\logger\collection_logger_query;
use block_xp\local\logger\collection_logger_with_query;
use block_xp\local\permission\access_logs_permissions;
use block_xp\local\sql\limit;
use block_xp\local\utils\user_utils;
use coding_exception;
use local_xp\local\logger\log_with_rule;
use local_xp\local\mcp\invalid_params_exception;
use local_xp\local\mcp\schema;
use local_xp\local\mcp\tool_definition;
use local_xp\local\utils\agent_utils;

/**
 * List Level Up XP logs MCP tool.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_levelupxp_logs extends world_base {
    /**
     * Get the log list tool definition.
     *
     * @return tool_definition
     */
    public static function get_definition(): tool_definition {
        return new tool_definition(
            'list_levelupxp_logs',
            'List the logs',
            'Returns a paginated list of Level Up XP log records for a context.',
            [CONTEXT_COURSE, CONTEXT_SYSTEM],
            tool_definition::RISK_READ | tool_definition::RISK_PERSONAL,
            static::get_input_schema(),
            static::get_output_schema()
        );
    }

    /**
     * Get the log list input schema.
     *
     * @return schema
     */
    protected static function get_input_schema(): schema {
        $timezone = \core_date::get_user_timezone_object()->getName();
        return static::world_scoped_schema(array_filter([
            'group_id' => !static::is_sitewide()
                ? schema::integer([
                    'When set to a Moodle group ID, only logs for that group are returned.',
                    'Obtainable by calling list_levelupxp_groups.',
                ])->minimum(0)->optional()
                : null,
            'user_id' => schema::integer(
                [
                    'When set to a Moodle user ID, only logs for that user are returned.',
                    'Obtainable by calling resolve_levelupxp_user.',
                ]
            )->optional(),
            'rule_id' => schema::integer([
                'When set to a rule ID, only logs for that rule are returned.',
            ])->optional(),
            'date_from' => schema::string(
                [
                    'Optional inclusive lower time bound in format (Y-m-dTH:i:s, with optional +HH:MM).',
                    "When unspecified, uses user timezone {$timezone}.",
                    'Examples: 2026-05-09T00:00:00, 2026-05-09T00:00:00+10:00.',
                ]
            )->optional(),
            'date_to' => schema::string(
                [
                    'Optional inclusive upper time bound in format (Y-m-dTH:i:s, with optional +HH:MM).',
                    "When unspecified, uses user timezone {$timezone}.",
                    'Examples: 2026-05-09T23:59:59, 2026-05-09T23:59:59+10:00.',
                ]
            )->optional(),
            'page' => schema::integer('Page number, starting at 1. Defaults to 1.')->optional(),
            'perpage' => schema::integer([
                'Number of log entries per page.',
                'Defaults to 20 and is capped at 100.',
            ])->optional(),
        ]));
    }

    /**
     * Get the log list output schema.
     *
     * @return schema|null
     */
    protected static function get_output_schema(): ?schema {
        return agent_utils::paginated_results_schema(schema::object([
            'user_id' => schema::integer("The Moodle user ID. Don't expose unless asked."),
            'user_name' => schema::string('The user full name.'),
            'points' => schema::integer('The points associated with this log entry (can be zero).'),
            'reason' => schema::string('A short description of the reason for the points.'),
            'reason_location' => schema::string(
                'The location (activity, course, ...) associated with the reason for the points, if known.'
            )->nullable(),
            'rule' => schema::object([
                'rule_id' => schema::integer(
                    "Internal rule ID for this log row. Don't share the ID with the user unless they asked."
                ),
                'rule_type_name' => schema::string('The rule type display name.'),
                'rule_description' => schema::string('The human-readable description of the rule.'),
            ], 'The rule that awarded these points, if applicable and it has not been deleted.')->optional(),
            'time_recorded_utc' => schema::string(
                'The datetime as ISO-8601 with numeric offset (Y-m-dTH:i:sP) in UTC.'
            ),
            'time_recorded_display' => schema::string("The datetime in the user's timezone for display."),
        ]));
    }

    /**
     * Check access to Level Up XP logs.
     *
     * @return void
     */
    protected function permission_checks(): void {
        $accessperms = $this->get_world()->get_access_permissions();
        if (!$accessperms instanceof access_logs_permissions) {
            throw new coding_exception('Access permissions object is invalid.');
        }
        $accessperms->require_access_logs();
    }

    /**
     * Get a filtered page of Level Up XP logs.
     *
     * @return array
     */
    protected function execute() {
        $datefrom = $this->get_param('date_from', null);
        $dateto = $this->get_param('date_to', null);
        $datefromraw = $datefrom === null ? null : trim((string) $datefrom);
        $datetoraw = $dateto === null ? null : trim((string) $dateto);
        $usertz = \core_date::get_user_timezone_object();

        $datefromfallback = (new \DateTimeImmutable('now', $usertz))->setTime(0, 0, 0, 0);
        $datetofallback = (new \DateTimeImmutable('now', $usertz))->setTime(23, 59, 59, 0);
        $datefromdt = $datefromraw === null ? null : agent_utils::parse_datetime($datefromraw, $datefromfallback);
        $datetodt = $datetoraw === null ? null : agent_utils::parse_datetime($datetoraw, $datetofallback);

        if ((!empty($datefrom) && $datefromdt === null) || (!empty($dateto) && $datetodt === null)) {
            throw new \local_xp\local\mcp\invalid_params_exception(
                'Invalid params: date_from or date_to could not be parsed.'
            );
        } else if ($datefromdt && $datetodt && $datetodt < $datefromdt) {
            throw new \local_xp\local\mcp\invalid_params_exception(
                'Invalid params: date_from must be before or equal to date_to.'
            );
        }
        $world = $this->get_world();
        $logger = di::get('world_logger_factory')->get_logger_for_world($world);
        if (!$logger instanceof collection_logger_with_query) {
            throw new coding_exception('Collection logger must support querying.');
        }
        $perpage = max(1, min((int) $this->get_param('perpage', 20), 100));
        $page = max(1, min((int) $this->get_param('page', 1), 100));
        $filterruleid = $this->get_param('rule_id', null);

        // Defaults to all participants, and validate that it is a valid choice.
        $groupid = $this->get_param('group_id', 0);
        $grouppolicy = group_policy::from_world($world);
        if (!$grouppolicy->can_select_group($groupid)) {
            throw new invalid_params_exception('Invalid params: group_id was invalid or not provided.');
        }

        // Check the per-user filter.
        $filteruserid = $this->get_param('user_id', null);
        if (!empty($filteruserid) && !user_utils::is_valid_target($world->get_context(), $filteruserid)) {
            throw new invalid_params_exception('Invalid params: user_id is invalid.');
        }

        $query = new collection_logger_query();
        if ($filteruserid > 0) {
            $query->set_user_id($filteruserid);
        }
        if ($filterruleid > 0) {
            $query->set_rule_id($filterruleid);
        }
        $query->set_time_from($datefromdt);
        $query->set_time_to($datetodt);

        // Always apply the group filtering policy.
        $query->set_user_filter($grouppolicy->get_user_filter($groupid));
        $query->add_order_by('timerecorded', SORT_DESC);

        $total = $logger->count($query);
        $totalpages = max(1, (int) ceil($total / $perpage));

        $page = max(1, $page);
        if ($total === 0) {
            $page = 1;
        } else {
            $page = min($page, $totalpages);
        }
        $offset = ($page - 1) * $perpage;

        $ruledescriptor = \block_xp\di::get('rule_descriptor');

        $out = [];
        foreach ($logger->list($query, new limit($perpage, $offset)) as $log) {
            $reasonobj = $log->get_reason();
            $reasontext = '';
            if ($reasonobj instanceof \block_xp\local\reason\reason_with_short_description) {
                $reasontext = (string) $reasonobj->get_short_description();
            }
            $locationname = '';
            if ($reasonobj instanceof \local_xp\local\reason\reason_with_location) {
                $locationname = $reasonobj->get_location_name();
            }

            $timerecorded = $log->get_time_recorded();
            $ts = $timerecorded->getTimestamp();
            $entry = [
                'user_id' => $log->get_user_id(),
                'user_name' => fullname($log->get_user()),
                'points' => $log->get_points(),
                'reason' => $reasontext,
                'reason_location' => $locationname ?: null,
                'time_recorded_utc' => $timerecorded
                    ->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:sP'),
                'time_recorded_display' => userdate($ts, get_string('strftimedatetime', 'langconfig')),
            ];

            // TODO: Omit admin default rules once rule instances expose that distinction.
            $rule = $log instanceof log_with_rule ? $log->get_rule() : null;
            if ($rule) {
                $entry['rule'] = [
                    'rule_id' => $rule->get_id(),
                    'rule_type_name' => $ruledescriptor->get_type_name($rule),
                    'rule_description' => $ruledescriptor->get_description($rule),
                ];
            }

            $out[] = $entry;
        }

        $nextpage = null;
        if ($totalpages > 0 && $page < $totalpages) {
            $nextpage = $page + 1;
        }

        return [
            'current_page' => $page,
            'next_page' => $nextpage,
            'total_pages' => $totalpages,
            'items' => $out,
        ];
    }
}
