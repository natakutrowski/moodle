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
 * Router.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\routing;

use coding_exception;
use moodle_exception;

/**
 * Router.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class router {

    /** @var url_resolver The URL resolver. */
    protected $urlresolver;

    /**
     * Constructor.
     *
     * @param url_resolver $urlresolver The URL resolver.
     */
    public function __construct(url_resolver $urlresolver) {
        $this->urlresolver = $urlresolver;
    }

    /**
     * Dispatch.
     *
     * @return void
     */
    public function dispatch() {
        $uri = $this->urlresolver->get_route_url();
        $route = $this->urlresolver->match($uri);

        if (!$route) {
            throw new moodle_exception('Unknown route: ' . $uri);
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
        $url = $this->urlresolver->reverse($route->get_definition()->get_name(), $route->get_params());
        $request = new routed_request($method, $url, $route);

        $ctrl = $this->get_controller_from_request($request);
        $ctrl->handle($request);
    }

    /**
     * Find the controller from the request.
     *
     * @param routed_request $request The request.
     * @return \block_gearup\local\controller\controller
     */
    protected function get_controller_from_request(routed_request $request) {
        $route = $request->get_route();
        $name = $route->get_definition()->get_controller_name();
        $class = "block_gearup\\local\\controller\\{$name}";

        if (!class_exists($class)) {
            $requesturl = $request->get_url(false);
            throw new coding_exception('Controller for route not found.', "Route name: {$name}. Request URL: {$requesturl}.");
        }

        return new $class();
    }

}
