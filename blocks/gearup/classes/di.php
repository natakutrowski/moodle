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
 * Dependency manager.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup;
use coding_exception;

/**
 * Dependency injection class.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class di {

    /** @var container Our container. */
    protected static $container;

    /**
     * Get a thing.
     *
     * @param string $id The thing.
     * @return mixed
     */
    public static function get($id) {
        if (!static::$container) {
            static::$container = static::make_container();
        }

        // Get the clock from core if we can.
        if ($id === 'clock'
                && class_exists(\core\di::class)
                && interface_exists(\core\clock::class)
                && \core\di::get_container()->has(\core\clock::class)) {
            return \core\di::get_container()->get(\core\clock::class);
        }

        return static::$container->get($id);
    }

    /**
     * Make the container.
     *
     * @return container
     */
    protected static function make_container() {
        return new \block_gearup\local\default_container();
    }

    /**
     * Set the container.
     *
     * @param local\container $container The container.
     */
    public static function set_container(local\container $container) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('Containers can only be set during testing.');
        }
        self::$container = $container;
    }

}
