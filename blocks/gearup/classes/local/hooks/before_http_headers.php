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

namespace block_gearup\local\hooks;

use block_gearup\di;

/**
 * Hook callbacks for block_gearup
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_http_headers {

    /**
     * Callback.
     *
     * @param \core\hook\output\before_http_headers $hook The hook instance.
     * @return void
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        static::keep_drawer_open();
    }

    /**
     * Keep drawer open.
     */
    public static function keep_drawer_open(): void {
        global $CFG, $PAGE;

        if (during_initial_install() || isset($CFG->upgraderunning) || !get_config('block_gearup', 'version')) {
            return;
        }

        if (!\block_gearup\di::get('lm')->is_active()) {
            return;
        }

        if (get_config('block_gearup', 'keepdraweropen')) {
            $draweopenblock = get_user_preferences('drawer-open-block');
            $oncoursepage = strpos($PAGE->pagetype, 'course-view-') === 0;
            if (!$draweopenblock && $oncoursepage) {
                set_user_preference('drawer-open-block', true);
            }
        }
    }

}
