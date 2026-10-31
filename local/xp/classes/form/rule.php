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

namespace local_xp\form;

use block_xp\di;
use block_xp\local\ruletype\limit_spec;
use block_xp\local\ruletype\ruletype_with_limit;
use local_xp\local\rule\rule_with_limit;
use local_xp\local\rule\world_rule_manager;

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
     * Whether same as default.
     *
     * @param \stdClass $record
     * @param limit_spec $defaultlimit
     * @param limit_spec $defaultrepeatlimit
     * @return bool
     * @deprecated Since XP+ 21, use the world_rule_manager directly.
     */
    public static function is_same_as_default($record, limit_spec $defaultlimit, limit_spec $defaultrepeatlimit) {
        return world_rule_manager::is_same_as_default_limits($record, $defaultlimit, $defaultrepeatlimit);
    }
}
