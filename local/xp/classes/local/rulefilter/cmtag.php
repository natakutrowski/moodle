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

namespace local_xp\local\rulefilter;

use block_xp\local\action\tester\action_tester;
use block_xp\local\availability\has_availability_info;
use block_xp\local\availability\unavailability;
use block_xp\local\availability\availability_info;
use block_xp\local\availability\static_info;
use block_xp\local\rulefilter\rulefilter;
use context;
use lang_string;
use local_xp\local\action\tester\tagname_tester;
use local_xp\local\utils\tag_utils;

/**
 * Activity tag rule filter.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmtag implements has_availability_info, rulefilter {
    /** @var array Tag areas. */
    protected $areas = [['core', 'course_modules']];

    /**
     * Get action tester.
     *
     * @param context $effectivecontext The context.
     * @param object $config The config.
     * @return action_tester
     */
    public function get_action_tester(context $effectivecontext, object $config): action_tester {
        return new tagname_tester((string) ($config->char1 ?? ''), $this->areas);
    }

    /**
     * Get availability info.
     *
     * @return availability_info
     */
    public function get_availability_info(): availability_info {
        global $CFG;
        if (empty($CFG->usetags)) {
            return new static_info(false, [new unavailability('featuredisabled', new lang_string('tagsaredisabled', 'core_tag'))]);
        }
        return new static_info(true);
    }

    /**
     * Get compatible context levels.
     *
     * @return int[]
     */
    public function get_compatible_context_levels(): array {
        return [CONTEXT_SYSTEM, CONTEXT_COURSE];
    }

    /**
     * Get display name.
     *
     * @return lang_string
     */
    public function get_display_name(): lang_string {
        return new lang_string('rulefiltercmtag', 'block_xp');
    }

    /**
     * Get config label.
     *
     * @param object $config The config.
     * @param context|null $effectivecontext The context.
     * @return string
     */
    public function get_label_for_config(object $config, ?context $effectivecontext = null): string {
        $name = trim((string) ($config->char1 ?? ''));
        if ($name === '') {
            return '?';
        }

        $label = $name;
        if ($effectivecontext && !tag_utils::is_tag_used($name, $effectivecontext, $this->areas)) {
            $label .= ' – ' . get_string('notyetused', 'block_xp');
        }

        return $label;
    }

    /**
     * Get short description.
     *
     * @return lang_string
     */
    public function get_short_description(): lang_string {
        return new lang_string('rulefiltercmtagdesc', 'block_xp');
    }

    /**
     * Check admin compatibility.
     *
     * @return bool
     */
    public function is_compatible_with_admin(): bool {
        return true;
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
