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
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class render_utils {

    /**
     * Flatten select options.
     *
     * @param array $array The array to flatten.
     * @param mixed $selected The selected value.
     * @return array
     */
    public static function flatten_select_options($array, $selected = null) {
        $result = [];
        foreach ($array as $key => $value) {
            $options = [];
            $isoptgroup = false;
            $label = $value;

            if (is_array($value)) {
                $key = null;
                $label = array_keys($value)[0];
                $options = array_values($value)[0];
                $isoptgroup = true;
            }

            $result[] = [
                'value' => $key,
                'label' => $label,
                'selected' => $selected !== null ? $selected == $key : false,
                'isoptgroup' => $isoptgroup,
                'children' => static::flatten_key_value_pair($options, $selected),
            ];
        }
        return $result;
    }

    /**
     * Flatten a key value pair.
     *
     * @param array $array The array to flatten.
     * @param mixed $selected The selected value.
     * @return array
     */
    public static function flatten_key_value_pair($array, $selected = null) {
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = [
                'value' => $key,
                'label' => $value,
                'selected' => $selected !== null ? $selected == $key : false,
            ];
        }
        return $result;
    }

}
