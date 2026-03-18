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
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collection_utils {

    /**
     * Find an item in a collection.
     *
     * @param array $data
     * @param Closure $cb
     * @return mixed|null
     */
    public static function find($data, $cb) {
        foreach ($data as $value) {
            if ($cb($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Filter data from a prefix.
     *
     * This returns an object excluding keys starting with prefix.
     *
     * @param object|array $data The data.
     * @param string $prefix
     * @return object
     */
    public static function exclude_data_with_prefix($data, $prefix) {
        $finaldata = [];
        foreach ((array) $data as $key => $value) {
            if (strpos($key, $prefix) !== 0) {
                $finaldata[$key] = $value;
            }
        }
        return (object) $finaldata;
    }

    /**
     * Filter data from a prefix.
     *
     * This returns an object where keys start with prefix.
     *
     * @param object|array $data The data.
     * @param string $prefix
     * @return object
     */
    public static function filter_data_with_prefix($data, $prefix) {
        $finaldata = [];
        foreach ((array) $data as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $finaldata[$key] = $value;
            }
        }
        return (object) $finaldata;
    }

    /**
     * Iterable to array.
     *
     * This complements iterator_to_array that did not accept arrays prior to PHP 8.2.
     *
     * @param iterable $iterable
     * @return array
     */
    public static function iterable_to_array(iterable $iterable): array {
        return is_array($iterable) ? $iterable : iterator_to_array($iterable);
    }

    /**
     * Prefix data.
     *
     * Returns an object where all keys have been prefixed.
     *
     * @param object|array $data The data.
     * @param string $prefix
     * @return object
     */
    public static function prefix_data($data, $prefix) {
        $finaldata = [];
        foreach ((array) $data as $key => $value) {
            $finaldata[$prefix . $key] = $value;
        }
        return (object) $finaldata;
    }

    /**
     * Unprefix data.
     *
     * Returns an object where keys matching a prefix have been unprefixed.
     *
     * @param object|array $data The data.
     * @param string $prefix
     * @return object
     */
    public static function unprefix_data($data, $prefix) {
        $prefixlen = strlen($prefix);
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $data[substr($key, $prefixlen)] = $value;
                unset($data[$key]);
            }
        }
        return (object) $data;
    }

}
