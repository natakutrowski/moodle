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
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\utils;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class human_utils {

    /**
     * Returns an approximate duration.
     *
     * @param int $secs The seconds.
     * @return int
     */
    public static function duration_approx(int $secs): int {
        $scale = static::time_scale($secs);
        return floor($secs / $scale) * $scale;
    }

    /**
     * Returns a percentage.
     *
     * @param float $ratio The ratio.
     * @return int
     */
    public static function percentage(float $ratio): int {
        return $ratio > 0 ? min(100, max(1, floor($ratio * 100))) : 0;
    }

    /**
     * Returns the time scale for a duration.
     *
     * This is used to determine the most relevant unit of time to use when displaying a duration.
     *
     * @param int $secs The duration.
     * @return int
     */
    public static function time_scale(int $secs): int {
        $scale = 60;
        if ($secs > 2 * YEARSECS) {
            $scale = YEARSECS;
        } else if ($secs > 4 * WEEKSECS) {
            $scale = WEEKSECS;
        } else if ($secs > 2 * DAYSECS) {
            $scale = DAYSECS;
        } else if ($secs > 2 * HOURSECS) {
            $scale = HOURSECS;
        }
        return $scale;
    }

}
