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

/**
 * Routes config.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\routing;

use block_xp\local\routing\route_definition;
use block_xp\local\routing\routes_config as routes_config_interface;
use block_xp\local\routing\static_routes_config;

/**
 * Routes config.
 *
 * @package    local_xp
 * @copyright  2017 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class routes_config extends static_routes_config {
    /** @var route_definition[] The routes. */
    protected $routes;
    /** @var routes_config_interface Alt config. */
    protected $altconfig;

    /**
     * Constructor.
     *
     * @param routes_config_interface $altconfig Alternative config.
     */
    public function __construct(routes_config_interface $altconfig) {
        $this->altconfig = $altconfig;
        parent::__construct([
            new route_definition(
                'user_sources',
                '/report/:courseid/sources/:userid',
                '~^/report/(\d+)/sources/(\d+)$~',
                'user_sources',
                [
                    1 => 'courseid',
                    2 => 'userid',
                ]
            ),
            new route_definition(
                'points',
                '/points/:courseid',
                '~^/points/(\d+)$~',
                'points',
                [
                    1 => 'courseid',
                ]
            ),
            new route_definition(
                'admin/oauthclients',
                '/admin/oauthclients',
                '~^/admin/oauthclients$~',
                'admin_oauth_clients'
            ),
            new route_definition(
                'admin/mcptools',
                '/admin/mcptools',
                '~^/admin/mcptools$~',
                'admin_mcp_tools'
            ),
        ]);
    }

    /**
     * Get a route.
     *
     * @param string $name The route name.
     * @return route_definition
     */
    public function get_route($name) {
        if (!array_key_exists($name, $this->routes)) {
            return $this->altconfig->get_route($name);
        }
        return $this->routes[$name];
    }

    /**
     * Return an array of routes.
     *
     * @return route_definition[]
     */
    public function get_routes() {
        return array_merge($this->altconfig->get_routes(), $this->routes);
    }
}
