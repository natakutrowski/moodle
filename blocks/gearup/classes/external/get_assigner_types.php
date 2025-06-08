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
use block_gearup\local\exporter\assigner_type_exporter;
use context;

/**
 * External.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_assigner_types extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, ''),
        ]);
    }

    /**
     * External function.
     *
     * @param int $contextid The context ID.
     * @return array
     */
    public static function execute($contextid) {
        $params = self::validate_parameters(self::execute_parameters(), ['contextid' => $contextid]);
        $contextid = $params['contextid'];
        $context = context::instance_by_id($contextid);

        self::validate_context($context);

        if (!di::get('lm')->is_active()) {
            throw new \moodle_exception('pluginnotactivated', 'block_gearup');
        }

        $ap = di::get('access_permissions_factory')->get_permissions_for_context($context);
        $ap->require_manage();

        $otr = di::get('assigner_type_resolver');
        $renderer = di::get('renderer');

        // At the moment, all types are returned and multiple assigners of the same type are always allowed.
        return array_values(array_map(function($type) use ($context, $renderer) {
            $otr = new assigner_type_exporter($type, ['context' => $context]);
            return $otr->export($renderer);
        }, $otr->get_types()));
    }

    /**
     * Returns.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_multiple_structure(assigner_type_exporter::get_read_structure());
    }

}
