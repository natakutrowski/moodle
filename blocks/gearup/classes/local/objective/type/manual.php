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
 * Manual.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;

defined('MOODLE_INTERNAL') || die();

/**
 * Manual.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual implements type {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new manual_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typemanual', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typemanualdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return false;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        return false;
    }

}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manual_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];
        $mform->getElement('countneeded')->setLabel(get_string('counttoattain', 'block_gearup'));
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        return [];
    }

}
