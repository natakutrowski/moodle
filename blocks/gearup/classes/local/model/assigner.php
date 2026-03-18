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
 * Assigner persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\model;

use block_gearup\di;
use core\persistent;
use lang_string;

/**
 * Assigner persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assigner extends persistent {

    const TABLE = 'block_gearup_assigner';

    /**
     * Properties.
     *
     * @return array
     */
    public static function define_properties() {
        return [
            'missionid' => [
                'type' => PARAM_INT,
            ],
            'type' => [
                'type' => PARAM_RAW,
            ],
            'label' => [
                'type' => PARAM_TEXT,
                'default' => '',
            ],
            'enabled' => [
                'type' => PARAM_BOOL,
                'default' => 1,
            ],
            'configdata' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Get the config data.
     *
     * Convenience method that unpacks the config data when needed.
     *
     * @param mixed
     */
    protected function get_configdata() {
        $value = $this->raw_get('configdata');
        return $value === null ? null : json_decode($value);
    }

    /**
     * Resolve the type object.
     *
     * @return \block_gearup\local\assigner\type\type
     */
    protected function get_type_object() {
        $type = di::get('assigner_type_resolver')->get_type($this->raw_get('type'));
        if (!$type) {
            throw new \coding_exception('The type does not exist.');
        }
        return $type;
    }

    /**
     * Set the config data.
     *
     * Convenience method to set the config data into the persistent. This will take
     * care of setting the value in `configdata` as a JSON string, as well as spreading
     * the config to other parameters if needed.
     *
     * @param object|null $value
     */
    protected function set_configdata($value) {
        if ($value !== null && !is_object($value)) {
            throw new \coding_exception('The expected config data should be null or an object.');
        }
        $this->raw_set('configdata', $value === null ? null : json_encode($value));
    }

    /**
     * Validate configdata.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_configdata($value) {
        if ($value === null) {
            return true;
        }

        $value = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new lang_string('invaliddata', 'core_error');
        }

        return true;
    }

    /**
     * Validate type.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_type($value) {
        try {
            $type = di::get('assigner_type_resolver')->get_type($value);
        } catch (\moodle_exception $e) {
            $type = null;
        }
        if (!$type) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

}
