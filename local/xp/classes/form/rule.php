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
// https://levelup.plus

namespace local_xp\form;

use block_xp\di;
use block_xp\local\ruletype\limit_spec;
use block_xp\local\ruletype\ruletype_with_limit;
use local_xp\local\rule\rule_with_limit;

/**
 * Form.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule extends \block_xp\form\rule {

    /** @var object The local rule record. */
    protected $localrule;

    /**
     * Get the local rule.
     *
     * @return object The local rule record.
     */
    protected function get_local_rule() {
        if (!isset($this->localrule)) {
            $record = di::get('db')->get_record('local_xp_rule', ['ruleid' => $this->get_rule()->id]);
            if (!$record) {
                $record = (object) [
                    'ruleid' => $this->get_rule()->id,
                ];
            }
            $this->localrule = $record;
        }
        return $this->localrule;
    }

    /**
     * Get the default data.
     *
     * @return array
     */
    protected function get_default_data(): array {
        $data = parent::get_default_data();

        if ($this->supports_limits()) {
            $localrule = $this->get_local_rule();
            $values = array_filter([
                'limitmax' => $localrule->limitmax ?? null,
                'limitwindow' => $localrule->limitwindow ?? null,
                'repeatscope' => $localrule->repeatscope ?? null,
                'repeatwindow' => $localrule->repeatwindow ?? null,
            ], function ($value) {
                return $value !== null;
            });
            $data = array_merge($data, $values);
        }

        return $data;
    }

    /**
     * Save the data.
     *
     * @param \stdClass $data The data.
     */
    protected function save_data(\stdClass $data): void {
        parent::save_data($data);
        $db = di::get('db');

        if (!$this->supports_limits()) {
            $db->delete_records('local_xp_rule', ['ruleid' => $this->get_rule()->id]);
            return;
        }

        // Apply submitted values to the form.
        $record = $this->get_local_rule();
        $fields = ['limitmax', 'limitwindow', 'repeatscope', 'repeatwindow'];
        foreach ($fields as $field) {
            if (isset($data->{$field})) {
                $record->{$field} = $data->{$field};
            } else {
                $record->{$field} = null;
            }
        }

        // If all is the same as the default value, we just delete to avoid having lingering data.
        $ruletype = $this->get_ruletype();
        if ($ruletype instanceof ruletype_with_limit) {
            if (static::is_same_as_default($record, $ruletype->get_default_limit(), $ruletype->get_default_repeat_limit())) {
                $db->delete_records('local_xp_rule', ['ruleid' => $this->get_rule()->id]);
                return;
            }
        }

        // Save the record.
        if (empty($record->id)) {
            $record->id = $db->insert_record('local_xp_rule', $record);
        } else {
            $db->update_record('local_xp_rule', $record);
        }
    }

    /**
     * Whether same as default.
     *
     * @param \stdClass $record
     * @param limit_spec $defaultlimit
     * @param limit_spec $defaultrepeatlimit
     * @return bool
     */
    public static function is_same_as_default($record, limit_spec $defaultlimit, limit_spec $defaultrepeatlimit) {
        $record->limitmax ??= null;
        $record->limitwindow ??= null;
        $record->repeatscope ??= null;
        $record->repeatwindow ??= null;

        // When both are null, we're using default. Time windows are not relevant when the primary value is null.
        if ($record->limitmax === null && $record->repeatscope === null) {
            return true;
        }

        $defaultmax = $defaultlimit->get_max();
        $defaultscope = $defaultrepeatlimit->get_scope();

        $samelimit = $record->limitmax === null || $record->limitmax == $defaultmax;
        $samewindow = $record->limitmax === null || $record->limitwindow === null
            || $record->limitwindow == $defaultlimit->get_time_window();

        $samerepeatscope = $record->repeatscope === null || $record->repeatscope == $defaultrepeatlimit->get_scope();
        $samerepeatwindow = $record->repeatscope === null || $record->repeatwindow === null
            || $record->repeatwindow == $defaultrepeatlimit->get_time_window();

        // When max is 0, time window is irrelevant. And when scope is NONE, window is irrelevant too.
        return (($samelimit && ($defaultmax === 0 || $samewindow))
            && ($samerepeatscope && ($defaultscope === limit_spec::SCOPE_NONE || $samerepeatwindow)));
    }
}
