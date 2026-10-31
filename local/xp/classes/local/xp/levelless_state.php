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
 * Level-less state.
 *
 * @package    local_xp
 * @copyright  2019 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\xp;

use block_xp\local\xp\described_level;
use block_xp\local\xp\state;
use block_xp\local\xp\state_with_subject;

/**
 * Level-less state.
 *
 * Simple implementation where the level is not computed, but a progress bar can
 * be computed if the total amount of points is shared.
 *
 * @package    local_xp
 * @copyright  2019 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class levelless_state implements state, state_with_subject {
    /** @var int The state ID. */
    protected $id;
    /** @var string The name. */
    protected $name;
    /** @var int Total XP. */
    protected $totalxp;
    /** @var int The XP. */
    protected $xp;

    /**
     * Constructor.
     *
     * @param int $xp The cohort XP.
     * @param int $id The ID.
     * @param string $name The name.
     * @param int|null $totalxp The total amount of XP to display a progress bar.
     */
    public function __construct($xp, $id, $name, $totalxp = null) {
        $this->name = $name;
        $this->id = $id;
        $this->xp = $xp;
        $this->totalxp = $totalxp;
    }

    /**
     * Get ID.
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get level.
     *
     * @return level
     */
    public function get_level() {
        return new described_level(1, 1, '');
    }

    /**
     * Get link.
     *
     * @return moodle_url|null
     */
    public function get_link() {
        return null;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get picture.
     *
     * @return moodle_url|null
     */
    public function get_picture() {
        return null;
    }

    /**
     * Get level ratio.
     *
     * @return float
     */
    public function get_ratio_in_level() {
        $total = $this->get_total_xp_in_level();
        if ($total <= 0) {
            return 1;
        }
        return min(1, max(0, $this->get_xp_in_level() / $total));
    }

    /**
     * Get level XP.
     *
     * @return int
     */
    public function get_total_xp_in_level() {
        return $this->totalxp ? $this->totalxp : 1;
    }

    /**
     * Get XP.
     *
     * @return int
     */
    public function get_xp() {
        return $this->xp;
    }

    /**
     * Get XP in level.
     *
     * @return int
     */
    public function get_xp_in_level() {
        return $this->totalxp ? min($this->xp, $this->totalxp) : 1;
    }
}
