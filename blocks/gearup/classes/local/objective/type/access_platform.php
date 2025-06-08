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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use block_gearup\local\action\action;
use block_gearup\local\action\course_accessed;
use block_gearup\local\action\dashboard_viewed;
use block_gearup\local\action\frontpage_viewed;
use block_gearup\local\action\logged_in;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\time\frequency_evaluator;
use block_gearup\local\utils\time_utils;
use DateTimeImmutable;

defined('MOODLE_INTERNAL') || die();

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_platform implements type, type_with_state_reevaluation {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $this->evaluate_state($instance, $action->get_time());
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new access_platform_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeaccessplatform', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeaccessplatformdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        // There is a limitation here. When the objective is set in the context of a course,
        // and another course is being viewed, we may not capture that the platform was accessed. That is
        // because the course_accessed context would be that of another course and the we would therefore
        // not resolve the incomplete objective in the context of the current course.
        return $action instanceof logged_in
            || $action instanceof course_accessed
            || $action instanceof frontpage_viewed
            || $action instanceof dashboard_viewed;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        $evaluation = $this->get_evaluator($action->get_time(), $instance);
        return $evaluation->is_valid() || $evaluation->is_late();
    }

    public function reevaluate_state(objective_instance $instance) {
        $this->evaluate_state($instance, new DateTimeImmutable(), true);
    }

    /**
     * Evaluate the state.
     *
     * @param objective_instance $instance The instance.
     * @param DateTimeImmutable $now The time representing now.
     * @param bool $stalecheckonly Whether we just perform the stale check.
     */
    protected function evaluate_state(objective_instance $instance, DateTimeImmutable $now, $stalecheckonly = false) {
        $state = $this->get_normalized_state($instance->get_type_state());
        $evaluator = $this->get_evaluator($now, $instance);

        // TODO Wrap all in a transaction?

        // When late, we need to reset the counter.
        if ($evaluator->is_late()) {
            $instance->reset_counter();
            $instance->set_dormant_until(null);
            $instance->set_stale_from(null);
        }

        // When valid (or late), we increment the counter.
        if (!$stalecheckonly && ($evaluator->is_late() || $evaluator->is_valid())) {
            $state->la = $now->getTimestamp();
            $instance->increment_counter(1);
            $instance->set_type_state($state);
            $instance->set_dormant_until($evaluator->get_dormant_until());
            $instance->set_stale_from($evaluator->get_stale_from());
        }
    }

    /**
     * Get the evaluation.
     *
     * @param DateTimeImmutable $now The time now (will be set to the user's timezone).
     * @param objective_instance $instance The instance.
     * @return frequency_evaluator
     */
    protected function get_evaluator(DateTimeImmutable $now, objective_instance $instance) {
        $state = $this->get_normalized_state($instance->get_type_state());
        $config = $instance->get_objective()->get_type_config();
        $mode = $config->mode ?? frequency_evaluator::MODE_NONE;

        $tz = time_utils::get_user_timezone($instance->get_subject_id());
        return new frequency_evaluator($mode, time_utils::make_datetime($state->la ?? 0, $tz), $now);
    }

    /**
     * Normalize the state.
     *
     * @param mixed $state The state.
     * @return object
     */
    protected function get_normalized_state($state) {
        return $state ?? (object) ['la' => 0];
    }

}

/**
 * Login config form extender.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_platform_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        $els[] = $mform->addElement('select', 'cd_mode', get_string('accesscounts', 'block_gearup'),
            frequency_evaluator::get_form_options());

        if (!isset($mform->_defaults['cd_mode'])) {
            $mform->setDefault('cd_mode', frequency_evaluator::MODE_DAY);
        }

        $el = $mform->getElement('countneeded');
        $el->setLabel(get_string('howmanytimes', 'block_gearup'));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        return [];
    }

}
