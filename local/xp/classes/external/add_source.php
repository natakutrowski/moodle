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

namespace local_xp\external;

use block_xp\di;
use block_xp\external\external_function_parameters;
use block_xp\external\external_value;
use local_xp\local\world\global_world;

/**
 * External function.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_source extends external_api {
    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT),
            'sourcecontextid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $contextid The context ID.
     * @param string $sourcecontextid The context ID of the source.
     * @return array
     */
    public static function execute($contextid, $sourcecontextid) {
        $params = self::validate_parameters(self::execute_parameters(), compact('contextid', 'sourcecontextid'));
        $contextid = $params['contextid'];
        $sourcecontextid = $params['sourcecontextid'];

        $world = di::get('context_world_factory')->get_world_from_context(\context::instance_by_id($contextid));
        self::validate_context($world->get_context());
        di::get('addon')->require_activated();
        $world->get_access_permissions()->require_manage();

        if (!$world instanceof global_world) {
            throw new \moodle_exception('errorfeaturenotenabled', 'block_xp');
        }

        // We only accept courses and reject the frontpage.
        $sourcecontext = \context::instance_by_id($sourcecontextid);
        if (!$sourcecontext instanceof \context_course || $sourcecontext->instanceid == SITEID) {
            throw new \moodle_exception('invaliddata', 'core_error');
        }

        $modinfo = get_fast_modinfo($sourcecontext->instanceid);
        if (!can_access_course($modinfo->get_course(), null, '', true)) {
            throw new \moodle_exception('requireloginerror', 'core_error');
        }

        $db = di::get('db');
        $params = ['contextid' => $world->get_context()->id, 'sourcecontextid' => $sourcecontext->id];
        if (!$db->record_exists('local_xp_source', $params)) {
            $db->insert_record('local_xp_source', $params);
        }

        return true;
    }

    /**
     * External function return values.
     *
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL);
    }
}
