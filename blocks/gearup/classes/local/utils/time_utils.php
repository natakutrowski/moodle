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

use DateTimeImmutable;
use DateTimeZone;

/**
 * Utils.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class time_utils {

    /** Daily only week days. */
    const DAILY_WEEKDAY = 120960; // That is calculated from 86400 * 7/5.

    /**
     * Get a user's timezone.
     *
     * @param int|object $userorid The user, or its ID.
     * @return DateTimeZone
     */
    public static function get_user_timezone($userorid) {
        global $USER;
        $user = $userorid;

        if (!is_object($userorid)) {
            if ($userorid == $USER->id) {
                $user = $USER;
            }
            $user = \core_user::get_user($userorid, '*', MUST_EXIST);
        }

        return \core_date::get_user_timezone_object($user);
    }

    /**
     * Make a date time from timestamp.
     *
     * It's best to use a convenience method because the constructor of
     * the DateTime class does not support passing a timestamp with a timezone.
     *
     * @param int $timestamp The timestamp.
     * @param DateTimeZone $tz The timezone.
     * @return DateTimeImmutable
     */
    public static function make_datetime(int $timestamp, DateTimeZone $tz) {
        $dt = new DateTimeImmutable();
        return $dt->setTimestamp($timestamp)->setTimezone($tz);
    }

}
