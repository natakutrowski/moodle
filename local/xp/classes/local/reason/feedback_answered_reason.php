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

use context;
use local_xp\local\utils\context_utils;
use moodle_url;

/**
 * Feedback answered reason.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_answered_reason extends \block_xp\local\reason\feedback_answered_reason implements reason_with_location {

    public function get_location_name() {
        return context_utils::get_activity_name($this->get_env_id());
    }

    public function get_location_url() {
        $context = context::instance_by_id($this->get_env_id() ?? 0, IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_MODULE) {
            return null;
        }
        if (!\block_xp\di::get('db')->record_exists('feedback_completed', ['id' => (int) $this->get_object_id()])) {
            return context_utils::get_url($context);
        }
        return new moodle_url('/mod/feedback/show_entries.php', [
            'id' => (int) $context->instanceid,
            'showcompleted' => (int) $this->get_object_id(),
        ]);
    }

}
