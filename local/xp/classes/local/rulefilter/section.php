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

/**
 * Filter.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_xp\local\rulefilter;

use block_xp\local\action\tester\action_tester;
use block_xp\local\rulefilter\rulefilter;
use context;
use lang_string;

/**
 * Filter.
 *
 * @package    local_xp
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section implements rulefilter {
    /**
     * Get action tester.
     *
     * @param context $effectivecontext The context.
     * @param object $config The config.
     * @return action_tester
     */
    public function get_action_tester(context $effectivecontext, object $config): action_tester {
        return new section_tester($config->int1 ?? 0);
    }

    /**
     * Get compatible context levels.
     *
     * @return int[]
     */
    public function get_compatible_context_levels(): array {
        return [CONTEXT_COURSE];
    }

    /**
     * Get display name.
     *
     * @return lang_string
     */
    public function get_display_name(): lang_string {
        return new lang_string('rulefiltersection', 'block_xp');
    }

    /**
     * Get config label.
     *
     * @param object $config The config.
     * @param context|null $effectivecontext The context.
     * @return string
     */
    public function get_label_for_config(object $config, ?context $effectivecontext = null): string {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $sectionnum = $config->int1 ?? 0;
        $coursecontext = $effectivecontext ? $effectivecontext->get_course_context(false) : null;
        if ($coursecontext) {
            $courseid = (int) $coursecontext->instanceid;
            try {
                return get_string('colon', 'block_xp', (object) [
                    'a' => '#' . $sectionnum,
                    'b' => get_section_name($courseid, $sectionnum),
                ]);
            } catch (\moodle_exception $e) {
                $e = $e; // Happy linter, happy coder.
            }
        }
        return get_string('unknownsectiona', 'block_xp', $sectionnum);
    }

    /**
     * Get short description.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string {
        return new lang_string('rulefiltersectiondesc', 'block_xp');
    }

    /**
     * Check admin compatibility.
     *
     * @return bool
     */
    public function is_compatible_with_admin(): bool {
        return false;
    }

    /**
     * Check whether multiple are allowed.
     *
     * @return bool
     */
    public function is_multiple_allowed(): bool {
        return true;
    }
}
