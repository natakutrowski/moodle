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

/**
 * Pre 5.2 compat.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\reportbuilder\entities;

use core_reportbuilder\local\entities\base;

defined('MOODLE_INTERNAL') || die();

if ($CFG->branch >= 502) {
    /**
     * Pre 5.2 compat.
     *
     * @package    local_xp
     * @copyright  2026 Frédéric Massart
     * @author     Frédéric Massart <fred@branchup.tech>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    trait pre_502_compat_trait {
    }
} else {
    /**
     * Pre 5.2 compat.
     *
     * @package    local_xp
     * @copyright  2026 Frédéric Massart
     * @author     Frédéric Massart <fred@branchup.tech>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    trait pre_502_compat_trait { // @codingStandardsIgnoreLine

        /**
         * Initialise the entity.
         *
         * @return base
         */
        public function initialise(): base {
            if (method_exists($this, 'get_available_columns')) {
                foreach ($this->get_available_columns() as $column) {
                    $this->add_column($column);
                }
            }

            if (method_exists($this, 'get_available_filters')) {
                foreach ($this->get_available_filters() as $filter) {
                    $this->add_filter($filter);
                }
            }

            if (method_exists($this, 'get_available_conditions')) {
                foreach ($this->get_available_conditions() as $condition) {
                    $this->add_condition($condition);
                }
            }

            return $this;
        }
    }
}
