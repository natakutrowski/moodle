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
use block_xp\local\availability\availability_info;
use block_xp\local\availability\has_availability_info;
use block_xp\local\availability\static_info;
use block_xp\local\icon\fa_icon;
use block_xp\local\icon\icon;
use block_xp\local\icon\with_iconography;
use block_xp\local\reason\reason;
use block_xp\local\ruletype\limit_spec;
use block_xp\local\ruletype\profile\cm_profile;
use block_xp\local\ruletype\profile\profile;
use block_xp\local\ruletype\repeat_option;
use block_xp\local\ruletype\ruletype;
use block_xp\local\ruletype\ruletype_deprecation_filler_trait;
use block_xp\local\ruletype\ruletype_with_goal;
use block_xp\local\ruletype\ruletype_with_limit;
use block_xp\local\ruletype\ruletype_with_profile;
use lang_string;
use local_xp\local\action\certificate_issued;
use local_xp\local\availability\plugin_required_info;
use local_xp\local\reason\certificate_obtained_reason;

/**
 * Obtain certificate rule type.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class obtain_certificate implements
    has_availability_info,
    ruletype,
    ruletype_with_goal,
    ruletype_with_limit,
    ruletype_with_profile,
    with_iconography {
    use ruletype_deprecation_filler_trait;

    /**
     * Get availability info.
     *
     * @return availability_info
     */
    public function get_availability_info(): availability_info {
        $customcert = new plugin_required_info('mod_customcert', 'Custom certificate', 2025041400, 'v5.0');
        $coursecertificate = new plugin_required_info('mod_coursecertificate', 'Course certificate', 2024100800, 'v4.4.4');
        $isavailable = $customcert->is_available() || $coursecertificate->is_available();
        return new static_info(
            $isavailable,
            $isavailable ? [] : array_merge($customcert->get_reasons(), $coursecertificate->get_reasons())
        );
    }

    /**
     * Get default limit.
     *
     * @return limit_spec
     */
    public function get_default_limit(): limit_spec {
        return new limit_spec(0);
    }

    /**
     * Get default repeat limit.
     *
     * @return limit_spec
     */
    public function get_default_repeat_limit(): limit_spec {
        return new limit_spec(1, limit_spec::WINDOW_NONE, limit_spec::SCOPE_ENV);
    }

    /**
     * Get display name.
     *
     * @return lang_string
     */
    public function get_display_name(): lang_string {
        return new lang_string('ruletypeobtaincertificate', 'block_xp');
    }

    /**
     * Get education goal.
     *
     * @return string
     */
    public function get_education_goal(): string {
        return self::GOAL_ASSESS;
    }

    /**
     * Get icon.
     *
     * @return icon|null
     */
    public function get_icon(): ?icon {
        return new fa_icon('certificate');
    }

    /**
     * Get profile.
     *
     * @return profile
     */
    public function get_profile(): profile {
        return (new cm_profile())->set_cm_types(['customcert', 'coursecertificate']);
    }

    /**
     * Get repeat limit options.
     *
     * @return repeat_option[]
     */
    public function get_repeat_limit_options(): array {
        return [
            (new repeat_option(limit_spec::SCOPE_ENV))
                ->set_once_label(new lang_string('onceperactivity', 'block_xp')),
        ];
    }

    /**
     * Get short description.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string {
        return new lang_string('ruletypeobtaincertificatedesc', 'block_xp');
    }

    /**
     * Check action compatibility.
     *
     * @param action $action The action.
     * @return bool
     */
    public function is_action_compatible(action $action): bool {
        return $action instanceof certificate_issued;
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
        if (!$action instanceof certificate_issued) {
            throw new \coding_exception('Incompatible action.');
        }
        $reason = new certificate_obtained_reason();
        $reason->set_env_id((int) $action->get_context()->id);
        $reason->set_object_id((int) $action->get_issue_id());
        return $reason;
    }
}
