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

namespace local_xp\local\serializer;

use block_xp\local\rulefilter\handler;
use local_xp\local\rule\rule_with_limit;

/**
 * Serializer.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_serializer extends \block_xp\local\serializer\rule_serializer {

    /** @var \block_xp\local\serializer\serializer The limit spec serializer. */
    protected $limitspecserializer;

    /**
     * Set the limit spec serializer.
     *
     * @param \block_xp\local\serializer\serializer $limitspecserializer The serializer.
     * @return self
     */
    public function set_limit_spec_serializer(\block_xp\local\serializer\serializer $limitspecserializer): self {
        $this->limitspecserializer = $limitspecserializer;
        return $this;
    }

    /**
     * Serialize.
     *
     * @param \block_xp\local\rule\instance $instance The rule instance.
     * @return array
     */
    public function serialize($instance) {
        global $CFG;

        $data = parent::serialize($instance);
        $data['limit'] = null;
        $data['repeatlimit'] = null;

        // Limits only apply when points are non-zero.
        if ($instance instanceof rule_with_limit && $this->limitspecserializer && $instance->get_points() > 0) {
            $limit = $instance->get_limit();
            $repeatlimit = $instance->get_repeat_limit();
            $data['limit'] = $limit ? $this->limitspecserializer->serialize($limit) : null;
            $data['repeatlimit'] = $repeatlimit ? $this->limitspecserializer->serialize($repeatlimit) : null;
        }

        // Older Moodle versions do not support null values in place of arrays.
        if ($CFG->branch < 403) {
            if ($data['limit'] === null) {
                unset($data['limit']);
            }
            if ($data['repeatlimit'] === null) {
                unset($data['repeatlimit']);
            }
        }

        return $data;
    }
}
