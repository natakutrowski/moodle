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
 * Create a discussion.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\discussion_created;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Create a discussion.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_discussion implements type {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $instance->increment_counter(1);
        $config = $instance->get_objective()->get_type_config();

        if (!empty($config->uniqueness)) {
            $state = $this->get_normalized_state($instance);
            $state->contextids[] = (int) $action->get_context()->id;
            $instance->set_type_state($state);
        }
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): extender {
        return new create_discussion_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecreatediscussion', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typecreatediscussiondesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof discussion_created;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        $config = $instance->get_objective()->get_type_config();
        if (!empty($config->uniqueness)) {
            $state = $this->get_normalized_state($instance);
            if (in_array($action->get_context()->id, $state->contextids)) {
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
            $state = (object) [
                'contextids' => [],
            ];
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
class create_discussion_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        $els[] = $mform->addElement('select', 'cd_uniqueness', get_string('counts', 'block_gearup'), [
            0 => get_string('everytime', 'block_gearup'),
            1 => get_string('onceperforum', 'block_gearup'),
        ]);
        $mform->setDefault('cd_uniqueness', 1);

        if ($mform->elementExists('countneeded')) {
            $countneededel = $mform->getElement('countneeded');
            $countneededel->setLabel(get_string('howmanytimes', 'block_gearup'));
        }

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
    }

}
