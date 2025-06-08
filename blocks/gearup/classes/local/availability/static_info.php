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
 * Static info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

/**
 * Static info.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class static_info implements info {

    /** @var bool Whether available. */
    protected $isavailable;
    /** @var \lang_string[] The reasons. */
    protected $reasons;

    /**
     * Constructor.
     *
     * @param bool $isavailable Whether available.
     * @param array $reasons The reasons.
     */
    public function __construct(bool $isavailable, array $reasons = []) {
        $this->isavailable = $isavailable;
        $this->reasons = $reasons;
    }

    /**
     * Whether available.
     *
     * @return boolean
     */
    public function is_available(): bool {
        return $this->isavailable;
    }

    /**
     * Get reasons.
     *
     * Only useful when not {@link self::is_available} returns false.
     *
     * @return \lang_string[]
     */
    public function get_reasons(): array {
        return $this->reasons;
    }

}
