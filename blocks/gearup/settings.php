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

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    if (di::get('lm')->is_activated()) {
        $settings->add(new activation_status());
        $settings->add(new recommended_plugins_setting());
        $settings->add(new tracker_missions_order());
        $settings->add(new admin_setting_configcheckbox(
            'block_gearup/keepdraweropen',
            get_string('keepdraweropen', 'block_gearup'),
            get_string('keepdraweropen_desc', 'block_gearup'),
            0
        ));
    }
}
