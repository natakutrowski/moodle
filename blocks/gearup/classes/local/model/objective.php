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
// See <https://levelup.plus>.

/**
 * Objective persistent.
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
 * Objective persistent.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class objective extends persistent {
    /** Table. */
    const TABLE = 'block_gearup_objective';

    /**
     * After delete.
     *
     * @param bool $result Whether deletion succeeded.
     * @return void
     */
    protected function after_delete($result) {
        global $DB;

        // If there is no other objective at that existing sortorder, change all orders.
        if (
            $result && !$DB->record_exists(static::TABLE, [
                'missionid' => $this->get('missionid'), 'sortorder' => $this->get('sortorder'),
            ])
        ) {
            $DB->execute(
                'UPDATE {block_gearup_objective}
                    SET sortorder = sortorder - 1
                  WHERE missionid = ? AND sortorder > ?',
                [$this->get('missionid'), $this->get('sortorder')]
            );
        }
    }

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
            'sortorder' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'type' => [
                'type' => PARAM_RAW,
            ],
            'label' => [
                'type' => PARAM_TEXT,
            ],
            'countneeded' => [
                'type' => PARAM_INT,
                'default' => 1,
            ],
            'configdata' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'supportingurl' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
        ];
    }

    /**
     * Append new objectives to the mission.
     */
    protected function before_create() {
        global $DB;
        if ($this->get('sortorder') == 0) {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {block_gearup_objective} WHERE missionid = ?',
                [$this->get('missionid')]
            );
            $this->set('sortorder', (int) $max + 1);
        }
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
     * Get the supporting URL.
     *
     * This can be null, when not set. Or a number when set by the objective type, or a string for a URL.
     *
     * @return int|string|null
     */
    protected function get_supportingurl() {
        $value = $this->raw_get('supportingurl');
        if ($value === null) {
            return null;
        }
        if (is_number($value)) {
            return (int) $value;
        }
        return $value;
    }

    /**
     * Resolve the type object.
     *
     * @return \block_gearup\local\objective\type\type
     */
    protected function get_type_object() {
        $type = di::get('objective_type_resolver')->get_type($this->raw_get('type'));
        if (!$type) {
            throw new \coding_exception('The type does not exist.');
        }
        return $type;
    }

    /**
     * Swap positions.
     *
     * @param int $dir -1 to move up, 1 to move down.
     * @return void
     */
    protected function move(int $dir): void {
        global $DB;

        if (!$this->get('id')) {
            throw new \coding_exception('Cannot move an unsaved objective.');
        }

        $transaction = $DB->start_delegated_transaction();
        $comparison = $dir < 0 ? '<' : '>';
        $sort = $dir < 0 ? 'DESC' : 'ASC';
        $records = $DB->get_records_sql(
            "SELECT id, sortorder
               FROM {block_gearup_objective}
              WHERE missionid = ? AND (sortorder $comparison ? OR (sortorder = ? AND id $comparison ?))
           ORDER BY sortorder $sort, id $sort",
            [$this->get('missionid'), $this->get('sortorder'), $this->get('sortorder'), $this->get('id')],
            0,
            1,
        );
        $adjacent = reset($records);
        if ($adjacent) {
            $DB->set_field(static::TABLE, 'sortorder', $adjacent->sortorder - $dir, ['id' => $adjacent->id]);
            $DB->set_field(static::TABLE, 'sortorder', $adjacent->sortorder, ['id' => $this->get('id')]);
            $this->set('sortorder', $adjacent->sortorder);
        }
        $transaction->allow_commit();
    }

    /**
     * Move down.
     *
     * @return void
     */
    public function move_down(): void {
        $this->move(1);
    }

    /**
     * Move up.
     *
     * @return void
     */
    public function move_up(): void {
        $this->move(-1);
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
     * Set the supporting URL.
     *
     * @param int|string|null $value
     */
    protected function set_supportingurl($value) {
        if (is_number($value)) {
            $value = (int) $value;
        } else {
            $value = clean_param($value, PARAM_URL) ?: null;
        }
        $this->raw_set('supportingurl', $value === null ? null : json_encode($value));
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

        try {
            $type = $this->get_type_object();
        } catch (\moodle_exception $e) {
            return new lang_string('invaliddata', 'core_error');
        }

        // Future work: Decide whether we should validate the config structure at all
        // considering the type is already the one that generates it.
        // $structure = $type->get_config_structure();
        // try {
        // Future work: This fails when additional keys are left behind, we should take note of this
        // when we modify the config structure of an objective type.
        // \external_api::validate_parameters($structure, is_object($value) ? (array) $value : $value);
        // } catch (\invalid_parameter_exception $e) {
        // return new lang_string('invaliddata', 'core_error');
        // }.

        return true;
    }

    /**
     * Validate countneeded.
     *
     * @param mixed $value
     * @return bool|lang_string
     */
    protected function validate_countneeded($value) {
        if ($value <= 0) {
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
            $type = di::get('objective_type_resolver')->get_type($value);
        } catch (\moodle_exception $e) {
            $type = null;
        }
        if (!$type) {
            return new lang_string('invaliddata', 'core_error');
        }
        return true;
    }

    /**
     * Get all by mission ID.
     *
     * @param int $missionid The mission ID.
     * @return static[]
     */
    public static function get_all_by_missionid(int $missionid): array {
        return static::get_records_select('missionid = ?', [$missionid], 'sortorder ASC, id ASC');
    }
}
