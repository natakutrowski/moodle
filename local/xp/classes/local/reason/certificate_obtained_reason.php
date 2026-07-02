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

use block_xp\local\reason\reason;
use block_xp\local\reason\reason_deprecation_filler_trait;
use block_xp\local\reason\reason_rule_trait;
use block_xp\local\reason\reason_tracking_trait;
use block_xp\local\reason\reason_with_rule;
use block_xp\local\reason\reason_with_short_description;
use block_xp\local\reason\reason_with_tracking;
use context;
use local_xp\local\utils\context_utils;
use moodle_url;

/**
 * Certificate obtained reason.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate_obtained_reason implements
    reason,
    reason_with_location,
    reason_with_rule,
    reason_with_short_description,
    reason_with_tracking {
    use reason_deprecation_filler_trait;
    use reason_rule_trait;
    use reason_tracking_trait;

    /**
     * Get the context.
     *
     * @return context|null
     */
    protected function get_context() {
        return context::instance_by_id($this->get_env_id() ?? 0, IGNORE_MISSING) ?: null;
    }

    /**
     * Get the location name.
     *
     * @return string|null
     */
    public function get_location_name() {
        return context_utils::get_activity_name($this->get_context());
    }

    /**
     * Get the location URL.
     *
     * @return moodle_url|null
     */
    public function get_location_url() {
        return context_utils::get_url($this->get_context());
    }

    /**
     * Short description for logs.
     *
     * @return string
     */
    public function get_short_description() {
        return get_string('certificateobtained', 'block_xp');
    }
}
