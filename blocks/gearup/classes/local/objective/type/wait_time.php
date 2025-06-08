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
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\di;
use block_gearup\local\action\action;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use DateInterval;

defined('MOODLE_INTERNAL') || die();

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wait_time implements type, type_with_state_initialisation, type_with_state_reevaluation {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
    }

    /**
     * Evaluate the objective.
     *
     * @param objective_instance $instance The objective instance.
     * @param mission_instance $missioninst The mission instance.
     */
    protected function evaluate(objective_instance $instance, mission_instance $missioninst) {
        $mh = di::get('mission_helper');

        $hasstarted = $mh->has_started($missioninst);
        $relativetime = $missioninst->get_time_assigned(); // This is a fallback time.
        if ($hasstarted) {
            $relativetime = $missioninst->get_time_started();
        }

        $duration = $instance->get_objective()->get_type_config()->time ?? 0;
        if (!$duration) {
            return;
        }

        $interval = new DateInterval('PT' . $duration . 'S');
        $targettime = $relativetime->add($interval);

        // If the mission was started and we met the target time, it's all done!
        if ($hasstarted && $targettime->getTimestamp() <= time()) {
            $instance->increment_counter(1);
            return;
        }

        // Recheck when we're at the target time.
        $instance->set_dormant_until($targettime);
        $instance->set_stale_from($targettime);
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $this->evaluate($instance, $missioninst);
    }

    public function reevaluate_state(objective_instance $instance) {
        $repository = di::get('repository');
        try {
            $missioninst = $repository->get_instance($instance->get_mission_instance_id());
        } catch (\moodle_exception $e) {
            return;
        }
        $this->evaluate($instance, $missioninst);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new wait_time_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typewaittime', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typewaittimedesc', 'block_gearup');
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
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wait_time_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded');
        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $mform->addElement('duration', 'cd_time', get_string('howmuchtime', 'block_gearup'),
            ['units' => [MINSECS, HOURSECS, DAYSECS, WEEKSECS]]);
        $mform->setDefault('cd_time', HOURSECS);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data->cd_time < 1) {
            $errors[] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
