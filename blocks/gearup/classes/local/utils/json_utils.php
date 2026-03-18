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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_utils {

    /**
     * Decode to list.
     *
     * This ensures that the value we are extracting is a plain list, where the array
     * keys have not been set. Encoding JSON arrays can lead to decoding them to an
     * object, or an associative array with random keys if we are not paying attention.
     * This util ensures that are obtaining what we expect.
     *
     * @param string $string The JSON string.
     * @param array $fallback The fallback value, when decoding fails.
     * @return array
     */
    public static function decode_to_list($string, array $fallback = []) {
        $result = json_decode((string) $string, true);
        if (!is_array($result)) {
            return $fallback;
        }
        return array_values($result);
    }

    /**
     * Encode value as list.
     *
     * This prevents mishaps where an array is encoded as an object because its
     * keys aren't all numerical. This util drops the keys and thus ensures that
     * the array is encoded as intended.
     *
     * @param array $data The list to encode.
     * @return string The JSON string.
     */
    public static function encode_as_list(array $list) {
        return json_encode(array_values($list));
    }

}
