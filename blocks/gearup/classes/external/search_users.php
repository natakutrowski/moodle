<?php
// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\external;

use block_gearup\di;
use context;
use context_course;
use context_system;
use core_table\local\filter\filter;
use core_table\local\filter\filterset;
use core_table\local\filter\string_filter;
use core_user\table\participants_search;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_users extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, '', VALUE_REQUIRED),
            'query' => new external_value(PARAM_RAW, '', VALUE_REQUIRED),
        ]);
    }

    /**
     * External function.
     *
     * @param int $contextid The context ID.
     * @param string $query The search terms.
     * @return void
     */
    public static function execute($contextid, $query) {
        global $CFG;

        $params = static::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'query' => $query,
        ]);
        $query = $params['query'];

        // Normalise the context to be that of a course (excl. frontpage), or the system one.
        // This is somewhat similar to the logic present the participants page.
        $context = context::instance_by_id($contextid);
        $coursecontext = $context->get_course_context(false);
        $isfrontpage = $coursecontext instanceof context_course && $coursecontext->instanceid == SITEID;
        if (!$coursecontext || $isfrontpage) {
            $context = context_system::instance();
        }
        $courseid = $context instanceof context_system ? SITEID : $context->instanceid;

        // Validate context.
        self::validate_context($context);
        course_require_view_participants($context);

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        // Use the participants search to retrieve the users.
        $course = get_course($courseid);
        $filterset = new \core_user\table\participants_filterset();
        $filterset->add_filter(new string_filter('keywords', filter::JOINTYPE_ALL, [$query]));
        $filterset->set_join_type(filterset::JOINTYPE_ALL);
        $psearch = new participants_search($course, $context, $filterset);

        // Include the recommended sort algorithm.
        [$sortsql, $sortparams] = users_order_by_sql('', $query, $context);
        try {
            $recordset = $psearch->get_participants('1 = 1', array_merge([], $sortparams), "ORDER BY $sortsql", 0, 100);
        } catch (\moodle_exception $e) {
            if ($CFG->branch < 405) {
                throw $e;
            }
            // Try again without the 'ORDER BY' clause, see MDL-83290.
            $recordset = $psearch->get_participants('1 = 1', array_merge([], $sortparams), "$sortsql", 0, 100);
        }

        $extrafields = \core_user\fields::get_identity_fields($context, false);
        $users = [];
        foreach ($recordset as $record) {
            $user = static::prepare_user_from_record($record, $extrafields);
            $users[] = $user;
        }

        $recordset->close();

        return $users;
    }

    /**
     * Returns.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT),
                'fullname' => new external_value(PARAM_RAW),
                'extrafields' => new external_multiple_structure(
                    new external_single_structure([
                        'name' => new external_value(PARAM_RAW),
                        'value' => new external_value(PARAM_RAW),
                    ])
                ),
            ])
        );
    }

    /**
     * Prepare a user from the record.
     *
     * @param object $record The record.
     * @param array $extrafields The extra fields.
     */
    public static function prepare_user_from_record($record, $extrafields) {
        $user = (object)[
            'id' => $record->id,
            'fullname' => fullname($record),
            'extrafields' => [],
        ];
        foreach ($extrafields as $extrafield) {
            $user->extrafields[] = (object)[
                'name' => $extrafield,
                'value' => $record->$extrafield,
            ];
        }
        return $user;
    }
}
