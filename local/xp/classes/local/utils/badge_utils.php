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

namespace local_xp\local\utils;

/**
 * Badge utils.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class badge_utils {

    /**
     * Process manual award.
     *
     * @param int $userid Recipient user id.
     * @param int $issuerid Issuer user id.
     * @param int $roleid Issuer role id.
     * @param int $badgeid Badge id.
     */
    public static function process_manual_award(int $userid, int $issuerid, int $roleid, int $badgeid): void {
        global $CFG;

        if (is_callable([\core_badges\award_manager::class, 'process_manual_award'])) {
            \core_badges\award_manager::process_manual_award($userid, $issuerid, $roleid, $badgeid);
            return;
        }

        require_once($CFG->dirroot . '/badges/lib/awardlib.php');
        \process_manual_award($userid, $issuerid, $roleid, $badgeid);
    }
}
