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

namespace local_xp\local\ruletype;

use block_xp\local\action\action;
use block_xp\local\reason\reason;
use block_xp\local\ruletype\profile\cm_profile;
use block_xp\local\ruletype\profile\profile;
use block_xp\local\ruletype\ruletype;
use block_xp\local\ruletype\ruletype_deprecation_filler_trait;
use block_xp\local\ruletype\ruletype_with_profile;
use lang_string;
use local_xp\local\reason\activity_completion_reason;

/**
 * Type.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cm_completion implements ruletype, ruletype_with_profile {
    use ruletype_deprecation_filler_trait;

    /**
     * Get display name.
     *
     * @return lang_string
     */
    public function get_display_name(): lang_string {
        return new lang_string('ruletypecmcompletion', 'block_xp');
    }

    /**
     * Get profile.
     *
     * @return profile
     */
    public function get_profile(): profile {
        return (new cm_profile())->set_requires_completion_enabled(true);
    }

    /**
     * Get short description.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string {
        return new lang_string('ruletypecmcompletiondesc', 'block_xp');
    }

    /**
     * Check action compatibility.
     *
     * @param action $action The action.
     * @return bool
     */
    public function is_action_compatible(action $action): bool {
        return $action->get_type() === 'cm_completed';
    }

    /**
     * Check action requirements.
     *
     * @param action $action The action.
     * @return bool
     */
    public function is_action_satisfying_requirements(action $action): bool {
        return true;
    }

    /**
     * Make reason.
     *
     * @param action $action The action.
     * @return reason
     */
    public function make_reason(action $action): reason {
        return activity_completion_reason::from_context($action->get_context());
    }
}
