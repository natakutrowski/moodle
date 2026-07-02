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
// https://levelup.plus

namespace local_xp\external;

use block_xp\di;
use block_xp\external\external_api;
use block_xp\external\external_function_parameters;
use block_xp\external\external_single_structure;
use block_xp\external\external_value;
use block_xp\local\ruletype\ruletype_with_limit;
use context;
use context_system;
use local_xp\form\rule as rule_form;

/**
 * Set rule limits.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_rule_limits extends external_api {

    /**
     * Parameters.
     *
     * @return \core_external\external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'ruleid' => new external_value(PARAM_INT),
            'limits' => new external_single_structure([
                'limitmax' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'limitwindow' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'repeatscope' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'repeatwindow' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
            ]),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $ruleid
     * @param array $limits
     * @return array
     */
    public static function execute($ruleid, $limits) {
        $params = self::validate_parameters(self::execute_parameters(), compact('ruleid', 'limits'));
        $ruleid = $params['ruleid'];
        $limits = $params['limits'];

        di::get('addon')->require_activated();
        $db = di::get('db');

        // Fetch the rule and check permissions.
        $rule = $db->get_record('block_xp_rule', ['id' => $ruleid], '*', MUST_EXIST);
        $world = self::require_manage_permissions_and_get_world((int) $rule->contextid);
        if ($world) {
            di::get('world_rule_manager_factory')->get_rule_manager($world)->detach();
        }

        // Validate the rule type.
        $typeresolver = di::get('rule_type_resolver');
        $ruletype = $typeresolver->get_type($rule->type);
        if (!$ruletype instanceof ruletype_with_limit) {
            throw new \moodle_exception('invaliddata', 'core_error');
        }

        // Fetch the local rule.
        $record = $db->get_record('local_xp_rule', ['ruleid' => $ruleid]);
        if (!$record) {
            $record = (object) [
                'ruleid' => $ruleid,
            ];
        }

        // Apply the limits.
        foreach (['limitmax', 'limitwindow', 'repeatscope', 'repeatwindow'] as $field) {
            $record->{$field} = $limits[$field] ?? null;
        }

        // If the limits are the same as the default, we delete the local rule.
        if (rule_form::is_same_as_default($record, $ruletype->get_default_limit(), $ruletype->get_default_repeat_limit())) {
            $db->delete_records('local_xp_rule', ['ruleid' => $ruleid]);
        } else if (empty($record->id)) {
            $record->id = $db->insert_record('local_xp_rule', $record);
        } else {
            $db->update_record('local_xp_rule', $record);
        }

        return [
            'success' => true,
        ];
    }

    /**
     * Returns.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True'),
        ]);
    }

    /**
     * Require manage permissions for the given context.
     *
     * @param int $contextid The context ID, or 0 for admin defaults.
     * @return ?\block_xp\local\world
     */
    protected static function require_manage_permissions_and_get_world($contextid) {
        if (!$contextid) {
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/site:config', $context);
            return;
        }

        $worldfactory = di::get('context_world_factory');
        $world = $worldfactory->get_world_from_context(context::instance_by_id($contextid));
        self::validate_context($world->get_context());
        $world->get_access_permissions()->require_manage();
        return $world;
    }
}
