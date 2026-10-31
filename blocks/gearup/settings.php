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
// See <https://levelup.plus>.

/**
 * Settings.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_gearup\di;
use block_gearup\local\setting\activation_status;
use block_gearup\local\setting\recommended_plugins_setting;
use block_gearup\local\setting\tracker_missions_order;
use block_gearup\output\tracker;

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new activation_status());
    $settings->add(new recommended_plugins_setting());
    $settings->add(new admin_setting_configcheckbox(
        'block_gearup/keepdraweropen',
        get_string('keepdraweropen', 'block_gearup'),
        get_string('keepdraweropen_desc', 'block_gearup'),
        0
    ));

    $settings->add(new admin_setting_heading(
        'block_gearup/trackersettings',
        get_string('trackersettings', 'block_gearup'),
        get_string('trackersettingsdesc', 'block_gearup')
    ));
    if (di::get('lm')->is_activated()) {
        $settings->add(new tracker_missions_order());
    }
    $limitoptions = [1 => '1', 3 => '3', 5 => '5', -1 => get_string('nolimit', 'block_gearup')];
    $settings->add(new admin_setting_configselect(
        'block_gearup/trackermaxongoingquests',
        get_string('ongoingquestlimit', 'block_gearup'),
        get_string('ongoingquestlimitdesc', 'block_gearup'),
        3,
        $limitoptions
    ));
    $settings->add(new admin_setting_configselect(
        'block_gearup/trackersortongoingquests',
        get_string('ongoingquestorder', 'block_gearup'),
        get_string('ongoingquestorderdesc', 'block_gearup'),
        tracker::SORT_MOST_RELEVANT,
        [
            tracker::SORT_MOST_RELEVANT => get_string('mostrelevant', 'block_gearup'),
            tracker::SORT_ALPHABETICAL => get_string('alphabetical', 'block_gearup'),
        ]
    ));
    $settings->add(new admin_setting_configselect(
        'block_gearup/trackermaxachievements',
        get_string('highlightedachievementlimit', 'block_gearup'),
        get_string('highlightedachievementlimitdesc', 'block_gearup'),
        3,
        [1 => '1', 3 => '3', 5 => '5', 10 => '10', -1 => get_string('nolimit', 'block_gearup')]
    ));
}
