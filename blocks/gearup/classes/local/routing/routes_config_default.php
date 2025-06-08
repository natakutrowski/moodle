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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\routing;

/**
 * Routes config.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class routes_config_default implements routes_config {

    /** @var route_definition[] Routes. */
    protected $routes;

    /**
     * Constructor.
     */
    public function __construct() {
        $routes = [
            new route_definition(
                'my_achievements',
                '/my/achievements',
                '~^/my/achievements$~',
                'my_achievements'
            ),
            new route_definition(
                'my_quests',
                '/my/quests',
                '~^/my/quests$~',
                'my_quests'
            ),
            new route_definition(
                'missions',
                '/missions',
                '~^/missions$~',
                'missions'
            ),
            new route_definition(
                'insights',
                '/insights',
                '~^/insights$~',
                'insights'
            ),
            new route_definition(
                'library',
                '/library',
                '~^/library$~',
                'library'
            ),
            new route_definition(
                'streaks',
                '/streaks',
                '~^/streaks$~',
                'streaks'
            ),
            new route_definition(
                'users',
                '/users',
                '~^/users$~',
                'users'
            ),
            new route_definition(
                'user',
                '/users/:userid',
                '~^/users/(\d+)$~',
                'user',
                [
                    1 => 'userid',
                ]
            ),
            new route_definition(
                'mission_create',
                '/missions/create/:type',
                '~^/missions/create/([a-z]+)$~',
                'mission_wizard_create',
                [
                    1 => 'type',
                ]
            ),
            self::make_mission_route(),
            self::make_mission_route('advanced'),
            self::make_mission_route('assign'),
            self::make_mission_route('insights'),
            self::make_mission_route('users'),

            new route_definition(
                'mission_user',
                '/missions/:missionid/users/:userid',
                '~^/missions/(\d+)/users/(\d+)$~',
                'mission_user',
                [
                    1 => 'missionid',
                    2 => 'userid',
                ]
            ),
            new route_definition(
                'mission_instance',
                '/missions/:missionid/instances/:missioninstid',
                '~^/missions/(\d+)/instances/(\d+)$~',
                'mission_instance',
                [
                    1 => 'missionid',
                    2 => 'missioninstid',
                ]
            ),
            self::make_mission_route('wizard_identity'),
            self::make_mission_route('wizard_assignbehaviour'),
            self::make_mission_route('wizard_timing'),
            self::make_mission_route('wizard_objectives'),
            self::make_mission_route('wizard_outcomes'),
            self::make_mission_route('wizard_storyline'),
            self::make_mission_route('wizard_end'),

            new route_definition(
                'activation',
                '/activation',
                '~^/activation$~',
                'redirect',
            ),
            new route_definition(
                'inactive',
                '/inactive',
                '~^/inactive$~',
                'redirect',
            ),
        ];
        $this->routes = array_reduce($routes, function($carry, $route) {
            $carry[$route->get_name()] = $route;
            return $carry;
        }, []);
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

    /**
     * Make a typical mission route.
     *
     * @param string $name The slug name of the route.
     * @return route_definition
     */
    protected static function make_mission_route($name = '') {
        $prefix = $name ? '_' : '';
        $url = str_replace('_', '/', $name);
        $slash = $url ? '/' : '';
        return new route_definition(
            'mission' . $prefix . $name,
            '/missions/:missionid' . $slash . $url,
            '~^/missions/(\d+)' . $slash . preg_quote($url, '~') . '$~',
            'mission' . $prefix . $name,
            [
                1 => 'missionid',
            ]
        );
    }

    /**
     * Make a typical objective route.
     *
     * @param string $name The slug name of the route.
     * @return route_definition
     */
    protected static function make_objective_route($name = '') {
        $prefix = $name ? '_' : '';
        $url = str_replace('_', '/', $name);
        $slash = $url ? '/' : '';
        return new route_definition(
            'mission_objective' . $prefix . $name,
            '/missions/:missionid/objectives/:objectiveid' . $slash . $url,
            '~^/missions/(\d+)/objectives/(\d+)' . $slash . preg_quote($url, '~') . '$~',
            'mission_objective' . $prefix . $name,
            [
                1 => 'missionid',
                2 => 'objectiveid',
            ]
        );
    }

    /**
     * Make a typical outcome route.
     *
     * @param string $name The slug name of the route.
     * @return route_definition
     */
    protected static function make_outcome_route($name = '') {
        $prefix = $name ? '_' : '';
        $url = str_replace('_', '/', $name);
        $slash = $url ? '/' : '';
        return new route_definition(
            'mission_outcome' . $prefix . $name,
            '/missions/:missionid/outcomes/:outcomeid' . $slash . $url,
            '~^/missions/(\d+)/outcomes/(\d+)' . $slash . preg_quote($url, '~') . '$~',
            'mission_outcome' . $prefix . $name,
            [
                1 => 'missionid',
                2 => 'outcomeid',
            ]
        );
    }

}
