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

namespace block_gearup\local\xp\compat;

// phpcs:disable Generic.Classes.DuplicateClassName.Found
// phpcs:disable PSR12.Files.FileHeader.IncorrectOrder
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Reason.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if (interface_exists('local_xp\local\reason\reason_with_location')) {
    /**
     * Reason.
     *
     * @package    block_gearup
     * @copyright  2025 Frédéric Massart
     * @author     Frédéric Massart <fred@branchup.tech>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    interface reason_with_location extends \local_xp\local\reason\reason_with_location {
    }
} else {
    /**
     * Reason.
     *
     * @package    block_gearup
     * @copyright  2025 Frédéric Massart
     * @author     Frédéric Massart <fred@branchup.tech>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    interface reason_with_location extends reason {
    }
}
