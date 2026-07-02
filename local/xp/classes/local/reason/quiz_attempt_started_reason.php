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

namespace local_xp\local\reason;

use local_xp\local\utils\context_utils;
use moodle_url;

/**
 * Quiz attempt started reason.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_attempt_started_reason extends \block_xp\local\reason\quiz_attempt_started_reason implements reason_with_location {

    public function get_location_name() {
        return context_utils::get_activity_name($this->get_env_id());
    }

    public function get_location_url() {
        return new moodle_url('/mod/quiz/review.php', ['attempt' => (int) $this->get_object_id()]);
    }

}
