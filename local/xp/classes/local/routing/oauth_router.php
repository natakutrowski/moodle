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

use block_xp\di;
use coding_exception;
use block_xp\local\routing\routed_request;
use local_xp\local\oauth\oauth_exception;

/**
 * Router.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_router extends \block_xp\local\routing\router {
    /**
     * Dispatch the OAuth request.
     *
     * @return void
     */
    public function dispatch() {
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            try {
                $this->handle_cors();
                parent::dispatch();
            } catch (oauth_exception $e) {
                http_response_code($e->get_http_code());
                header('Cache-Control: no-store');
                header('Pragma: no-cache');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => $e->errorcode,
                    'error_description' => $e->get_description(),
                ]);
            } catch (\Throwable $e) {
                http_response_code(500);
                header('Cache-Control: no-store');
                header('Pragma: no-cache');
                header('Content-Type: application/json; charset=utf-8');
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
     * Handle CORS.
     */
    protected function handle_cors() {
        if (!defined('AJAX_SCRIPT') || !AJAX_SCRIPT) {
            return;
        }

        $origin = clean_param($_SERVER['HTTP_ORIGIN'] ?? '', PARAM_URL);
        $origins = explode("\n", (string) di::get('config')->get('mcpcorsorigins'));
        if ($origin && (in_array($origin, $origins, true) || in_array('*', $origins))) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Headers: Content-Type');
            header('Access-Control-Allow-Methods: GET, OPTIONS, POST');
            header('Access-Control-Expose-Headers: Content-Type, WWW-Authenticate');
        }

        $httpmethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
        if ($httpmethod !== 'OPTIONS') {
            return;
        }
        die();
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
            "local_xp\\local\\controller\\oauth\\{$name}_controller",
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
