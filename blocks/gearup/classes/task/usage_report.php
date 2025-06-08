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

/**
 * Task.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\task;

use block_gearup\di;

/**
 * Task.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usage_report extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('taskusagereport', 'block_gearup');
    }

    public function execute() {

        // Local sites are skipped.
        if ($this->is_local_site()) {
            return;
        } else if (defined('BEHAT_SITE_RUNNING')) {
            return;
        }

        // Always enabled for now.
        // if (!get_config('block_gearup', 'usagereport')) {
        // mtrace('Usage report is disabled, disabling task...');
        // static::set_enabled(false);
        // return;
        // }

        if ((int) get_config('block_gearup', 'lastusagereport') > time() - (DAYSECS * 21)) {
            mtrace('Last usage report is too recent, abandoning...');
            return;
        }

        di::get('usage_reporter')->report();
    }

    /**
     * Whether is a local site.
     *
     * @return bool
     */
    protected function is_local_site() {
        global $CFG;

        $url = new \moodle_url($CFG->wwwroot);
        $host = $url->get_host();
        $ip = cleanremoteaddr($host);

        if ($host === 'localhost') {
            return true;
        } else if ($ip && !ip_is_public($ip)) {
            return true;
        } else if (preg_match('/\.local$/', $host)) {
            return true;
        }

        return false;
    }

    /**
     * Enable or disable the task.
     *
     * @param bool $enabled Whether to enable the task.
     */
    public static function set_enabled($enabled) {
        $task = \core\task\manager::get_scheduled_task('\\' . static::class);
        if (!$task) {
            return;
        }
        $task->set_disabled(!$enabled);
        try {
            \core\task\manager::configure_scheduled_task($task);
        } catch (\moodle_exception $e) {
            return;
        }
    }
}
