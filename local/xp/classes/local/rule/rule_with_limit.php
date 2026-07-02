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

namespace local_xp\local\rule;

use block_xp\local\ruletype\limit_spec;

/**
 * Rule.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface rule_with_limit extends \block_xp\local\rule\instance {

    /**
     * Get the limit.
     *
     * @return limit_spec|null Nulls means use default, return a limit_spec to enforce a behaviour.
     */
    public function get_limit(): ?limit_spec;

    /**
     * Get the repeat limit.
     *
     * @return limit_spec|null Nulls means use default, return a limit_spec to enforce a behaviour.
     */
    public function get_repeat_limit(): ?limit_spec;
}
