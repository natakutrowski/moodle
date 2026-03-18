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
 * Receive message.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\message_received;
use block_gearup\local\availability\admin_setting_info;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Receive message.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class receive_message implements has_availability_info, type {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $config = $instance->get_objective()->get_type_config();
        $state = $this->get_normalized_state($instance);

        // Increment the counter.
        $instance->increment_counter(1);

        // When we require unique sends, save the state.
        if (!empty($config->uniqueness)) {
            $state->senders[] = $action->get_target_user_id();
            $instance->set_type_state($state);
        }
    }

    public function get_availability_info(): info {
        return new admin_setting_info('messaging', new lang_string('messaging', 'core_admin'));
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new receive_message_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typereceivemessage', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typereceivemessagedesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof message_received;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        $sendingfrom = $action->get_target_user_id();
        $issendingfromother = $action->get_user_id() !== $sendingfrom;
        if (!$issendingfromother) {
            return false;
        }

        $state = $this->get_normalized_state($instance);
        $config = $instance->get_objective()->get_type_config();

        // When each sender must be unique.
        if (!empty($config->uniqueness)) {
            if (in_array($sendingfrom, $state->senders)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the state.
     *
     * @param objective_instance $instance The instance.
     * @return object
     */
    protected function get_normalized_state(objective_instance $instance) {
        $state = $instance->get_type_state();
        if ($state === null) {
            $state = (object) ['senders' => []];
        }
        return $state;
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
class receive_message_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        $els[] = $mform->addElement('advcheckbox', 'cd_uniqueness', get_string('fromdifferentpeople', 'block_gearup'));
        $mform->addHelpButton('cd_uniqueness', 'fromdifferentpeople', 'block_gearup');

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
    }

}
