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

namespace local_xp\local\xp;

/**
 * User state with leaderboard participation.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_state extends \block_xp\local\xp\user_state implements
    state_with_leaderboard_participation,
    state_with_source_count {
    /** @var int|null The leaderboard participation flag, or null when unset. */
    protected $leaderboardparticipationflag = null;
    /** @var int The number of contributing source records. */
    protected $sourcecount = 0;

    /**
     * Get the leaderboard participation flag.
     *
     * @return int|null The flag, or null when unset.
     */
    public function get_leaderboard_participation_flag(): ?int {
        return $this->leaderboardparticipationflag;
    }

    /**
     * Set the leaderboard participation flag.
     *
     * @param int|null $flag The flag, or null when unset.
     */
    public function set_leaderboard_participation_flag(?int $flag): void {
        $this->leaderboardparticipationflag = $flag;
    }

    /**
     * Get the source count.
     *
     * @return int
     */
    public function get_source_count(): int {
        return $this->sourcecount;
    }

    /**
     * Set the source count.
     *
     * @param int $count The number of contributing source records.
     */
    public function set_source_count(int $count): void {
        $this->sourcecount = $count;
    }
}
