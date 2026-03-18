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
 * Objective instance persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\model;

use block_gearup\local\objective\type\type;
use core\persistent;
use lang_string;

/**
 * Objective instance persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective_inst extends persistent {

    const TABLE = 'block_gearup_objective_inst';

    /** @var type The objective type. */
    protected $type;

    /**
     * Properties.
     *
     * @return array
     */
    public static function define_properties() {
        return [
            'missioninstid' => [
                'type' => PARAM_INT,
            ],
            'subjectid' => [
                'type' => PARAM_INT,
            ],
            'objectiveid' => [
                'type' => PARAM_INT,
            ],
            'state' => [
                'type' => PARAM_INT,
                'default' => 0,
                'choices' => [
                    // TODO Replace these.
                    0, // Incomplete.
                    1, // Complete.
                ],
            ],
            'counter' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'statedata' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'dormantuntil' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'stalefrom' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Get the state data.
     *
     * Convenience method that unpacks the state data when needed.
     *
     * @param mixed
     */
    protected function get_statedata() {
        $value = $this->raw_get('statedata');
        return $value === null ? null : (object) json_decode($value, true);
    }

    /**
     * Increment the counter.
     *
     * @param int $value The amount.
     * @return void
     */
    public function increment_counter($value, $max) {
        global $DB;

        // Update directly in DB to prevent race conditions.
        $id = $this->get('id');
        $DB->execute("UPDATE {" . static::TABLE . "}
                         SET counter = (CASE WHEN counter + :v > :m THEN :m2 ELSE counter + :v2 END)
                       WHERE id = :id", [
            'v' => $value,
            'v2' => $value,
            'm' => $max,
            'm2' => $max,
            'id' => $id,
        ]);
        $counter = $DB->get_field(static::TABLE, 'counter', ['id' => $id], IGNORE_MISSING);

        $this->set('counter', (int) $counter);
    }

    /**
     * Set the state data.
     *
     * Convenience method to set the state data into the persistent. This will take
     * care of setting the value in `statedata` as a JSON string.
     *
     * @param object|null $value
     */
    protected function set_statedata($value) {
        if ($value !== null && !is_object($value)) {
            throw new \coding_exception('The expected state data should be null or an object.');
        }
        $this->raw_set('statedata', $value === null ? null : json_encode($value));
    }

    /**
     * Validate counter.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_counter($value) {
        if ($value < 0) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate dormant until.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_dormantuntil($value) {
        if ($value !== null && $value <= 0) {
            // Zero, or less, is not allowed to make database lookups straightforward.
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate stale from.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_stalefrom($value) {
        if ($value !== null && $value <= 0) {
            // Zero, or less, is not allowed to make database lookups straightforward.
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Validate statedata.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_statedata($value) {
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
     * Delete in bulk by objective ID.
     *
     * @param int $objectiveid The objective ID.
     */
    public static function delete_by_objective_id(int $objectiveid) {
        global $DB;
        $DB->delete_records(self::TABLE, ['objectiveid' => $objectiveid]);
    }

}
