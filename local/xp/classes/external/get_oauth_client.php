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
use context_system;
use local_xp\local\exporter\oauth_client_exporter;
use local_xp\local\model\oauth_client;

/**
 * Get OAuth client.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_oauth_client extends external_api {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $id The client ID.
     * @return object
     */
    public static function execute($id) {
        global $PAGE;

        $params = self::validate_parameters(self::execute_parameters(), compact('id'));
        $id = $params['id'];

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);
        di::get('addon')->require_activated();

        $client = oauth_client::get_record(['id' => $id], MUST_EXIST);
        $exporter = new oauth_client_exporter($client);
        return $exporter->export($PAGE->get_renderer('core'));
    }

    /**
     * Returns.
     *
     * @return \block_xp\external\external_single_structure
     */
    public static function execute_returns() {
        return oauth_client_exporter::get_read_structure();
    }
}
