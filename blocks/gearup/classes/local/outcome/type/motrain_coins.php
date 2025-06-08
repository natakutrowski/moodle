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
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\availability\plugin_required_info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_motrain\local\award\award;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class motrain_coins implements user_facing_type, has_availability_info, has_availability_info_for_user {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        if (!class_exists('block_motrain\manager')) {
            return;
        }

        $coins = (int) $config->coins;
        if ($coins <= 0) {
            return;
        }

        $award = new award($missioninst->get_subject_id(), $missioninst->get_mission()->get_context()->id);
        try {
            $award->give($coins);
        } catch (\moodle_exception  $e) {
            return;
        }
    }

    public function get_availability_info(): info {
        return new plugin_required_info('block_motrain', 'Motrain', 2023052200, 'v1.8');
    }

    public function get_availability_info_for_user(int $userid, \context $context): info {
        global $USER;
        if (!class_exists('block_motrain\manager')) {
            return new static_info(false);
        }
        return new permission_required_info('block/motrain:manage', $context, $USER->id);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new motrain_coins_config_form_extender();
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomemotraincoins', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomemotraincoinsdesc', 'block_gearup');
    }

}

/**
 * Form extender.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class motrain_coins_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];
        $els[] = $mform->addElement('text', 'cd_coins', get_string('coins', 'block_motrain'));
        $mform->addRule('cd_coins', get_string('err_required', 'core_form'), 'required', null, 'client');
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data->cd_coins <= 0) {
            $errors['cd_coins'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
