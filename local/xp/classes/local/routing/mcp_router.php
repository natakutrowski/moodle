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

namespace local_xp\local\routing;

use coding_exception;
use block_xp\local\routing\routed_request;

/**
 * Router.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mcp_router extends \block_xp\local\routing\router {
    /**
     * Dispatch the MCP request.
     *
     * @return void
     */
    public function dispatch() {
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            try {
                parent::dispatch();
            } catch (\Throwable $e) {
                http_response_code(500);
                header('Cache-Control: no-store');
                header('Pragma: no-cache');
                header('Content-Type: application/json; charset=utf-8');

                // Conform to OAuth error format.
                echo json_encode([
                    'error' => 'server_error',
                    'error_description' => $e->getMessage(),
                ]);
            }
            die();
        }
        parent::dispatch();
    }

    /**
     * Override to include local controllers.
     *
     * @param routed_request $request The request.
     * @return \block_xp\local\controller\controller
     */
    protected function get_controller_from_request(routed_request $request) {
        $route = $request->get_route();
        $name = $route->get_definition()->get_controller_name();

        $candidates = [
            "local_xp\\local\\controller\\mcp\\{$name}_controller",
        ];

        $class = null;
        foreach ($candidates as $candidate) {
            if (!class_exists($candidate)) {
                continue;
            }
            $class = $candidate;
            break;
        }

        if (!$class) {
            throw new coding_exception('Controller for route not found.');
        }

        return new $class();
    }
}
