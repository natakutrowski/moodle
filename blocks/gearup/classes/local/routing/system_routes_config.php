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
 * Routes config.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\routing;

/**
 * Routes config.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class system_routes_config implements routes_config {

    /** @var route_definition[] Routes. */
    protected $routes;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->routes = [
            'webhook' => new route_definition(
                'webhook',
                '/webhook',
                '~^/webhook$~',
                'system\\webhook'
            ),
            'activate' => new route_definition(
                'activate',
                '/activate',
                '~^/activate$~',
                'system\\activate'
            ),
            'objincr' => new route_definition(
                'objincr',
                '/objincr/:objectiveid/:secret/user/:user/incr-by/:amount',
                '~^/objincr/(\d+)/([a-z0-9]+)/user/([^ /]+)/incr-by/(\d+)/?$~',
                'system\\objincr',
                [
                    1 => 'objectiveid',
                    2 => 'secret',
                    3 => 'user',
                    4 => 'amount',
                ]
            ),
        ];
    }

    /**
     * Get a route.
     *
     * @param string $name The route name.
     * @return route_definition
     */
    public function get_route($name) {
        if (!isset($this->routes[$name])) {
            throw new \coding_exception('Unknown route named: ' . $name);
        }
        return $this->routes[$name];
    }

    /**
     * Return an array of routes.
     *
     * @return route_definition[]
     */
    public function get_routes() {
        return $this->routes;
    }

}
