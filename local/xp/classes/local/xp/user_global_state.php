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
 * User global state.
 *
 * @package    local_xp
 * @copyright  2019 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\xp;

/**
 * User global state.
 *
 * Marks the progression of the user towards the ultimate level instead of the next one.
 *
 * @package    local_xp
 * @copyright  2019 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_global_state extends user_state {
    /**
     * Get level XP.
     *
     * @return int
     */
    public function get_total_xp_in_level() {
        $levels = $this->levelsinfo->get_levels();
        $lastlevel = array_pop($levels);
        return $lastlevel->get_xp_required();
    }

    /**
     * Get XP in level.
     *
     * @return int
     */
    public function get_xp_in_level() {
        return min($this->get_total_xp_in_level(), $this->get_xp());
    }
}
