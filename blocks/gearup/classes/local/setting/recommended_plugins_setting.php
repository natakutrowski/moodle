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

namespace block_gearup\local\setting;

use block_gearup\di;
use core_plugin_manager;
use moodle_url;

/**
 * Recommended plugins setting.
 *
 * @package    block_gearup
 * @copyright  2025 Branch Up Pty Ltd
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recommended_plugins_setting extends static_setting {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('block_gearup/recommendedplugins', get_string('recommendedplugins', 'block_gearup'), '');
    }

    /**
     * Get HTML content.
     *
     * @return string
     */
    protected function get_html_content() {
        $pluginman = core_plugin_manager::instance();

        $plugins = array_map(function ($plugin) use ($pluginman) {
            $isinstalled = !empty($plugin['isinstalled']);
            if (!isset($plugin['isinstalled'])) {
                $plugininfo = $pluginman->get_plugin_info($plugin['component']);
                $isinstalled = !empty($plugininfo);
            }
            return array_merge($plugin, ['isinstalled' => $isinstalled]);
        }, [
            [
                'component' => 'availability_gearup',
                'name' => 'Level Up Quest Availability',
                'description' => get_string('pluginavailabilitygearupdesc', 'block_gearup'),
                'url' => new moodle_url('https://github.com/branchup/moodle-availability_gearup'),
            ],
            [
                'component' => 'filter_shortcodes',
                'name' => 'Shortcodes',
                'description' => get_string('pluginshortcodesdesc', 'block_gearup'),
                'url' => new moodle_url('https://moodle.org/plugins/filter_shortcodes'),
            ],
        ]);

        return di::get('renderer')->render_from_template('block_gearup/admin/setting/recommended_plugins', ['plugins' => $plugins]);
    }

}
