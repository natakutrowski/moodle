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
 * Info stack.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\availability;

/**
 * Info stack.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class info_stack implements info {

    /** @var info[] $stack The stack. */
    protected $stack;

    /**
     * Constructor.
     *
     * @param bool $isavailable Whether available.
     * @param info[] $reasons The reasons.
     */
    public function __construct(array $stack) {
        $this->stack = $stack;
    }

    public function is_available(): bool {
        foreach ($this->stack as $info) {
            if (!$info->is_available()) {
                return false;
            }
        }
        return true;
    }

    public function get_reasons(): array {
        $reasons = [];
        foreach ($this->stack as $info) {
            if ($info->is_available()) {
                continue;
            }
            $reasons = array_merge($reasons, $info->get_reasons());
        }
        return $reasons;
    }

}
