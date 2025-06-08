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
use block_gearup\local\utils\time_utils;
use DateInterval;
use DateTimeImmutable;

defined('MOODLE_INTERNAL') || die();

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class not_access_platform implements type, type_with_state_initialisation, type_with_state_reevaluation {

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $state = $this->get_normalized_state($instance);

        // The user did not login in time.
        if ($state->lb < $action->get_time()->getTimestamp()) {
            $instance->increment_counter(1);
            return;
        }

        $tz = time_utils::get_user_timezone($instance->get_subject_id());
        $nextday = $action->get_time()->setTimezone($tz)->add(new DateInterval('P1D'))->setTime(0, 0, 0, 0);
        $loginby = $this->get_login_by_date($action->get_time(), $instance);
        $state->lb = $loginby->getTimestamp();

        $instance->set_type_state($state);
        $instance->set_dormant_until($nextday); // Capture logins once per day.
        $instance->set_stale_from($loginby); // Set stale when should be marked as complete.
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new not_access_platform_config_form_extender();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typenotaccessplatform', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typenotaccessplatformdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof logged_in
            || $action instanceof course_accessed
            || $action instanceof frontpage_viewed
            || $action instanceof dashboard_viewed;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        return true;
    }

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $state = $this->get_normalized_state($instance);
        $loginby = $this->get_login_by_date(new DateTimeImmutable(), $instance);

        $state->lb = $loginby->getTimestamp();
        $instance->set_type_state($state);
        $instance->set_stale_from($loginby);
    }

    public function reevaluate_state(objective_instance $instance) {
        $state = $this->get_normalized_state($instance);

        $loginby = $state->lb ?? 0;
        if ($loginby <= time()) {
            $instance->increment_counter(1);
            return;
        }

        $instance->set_stale_from(new DateTimeImmutable('@' . $loginby));
    }

    /**
     * Get the login by date.
     *
     * @param DateTimeImmutable $reftime The reference time (today, last login, ...).
     * @param objective_instance $instance The objective instance.
     * @return DateTimeImmutable
     */
    protected function get_login_by_date(DateTimeImmutable $reftime, objective_instance $instance) {
        $tz = time_utils::get_user_timezone($instance->get_subject_id());

        $nextday = $reftime->setTimezone($tz)->add(new DateInterval('P1D'))->setTime(0, 0, 0, 0);
        $config = $instance->get_objective()->get_type_config();
        $days = floor(max(DAYSECS, $config->time ?? 0) / DAYSECS);

        return $nextday->add(new DateInterval("P{$days}D"));
    }

    /**
     * Normalize the state.
     *
     * @param mixed $state The state.
     * @return object
     */
    protected function get_normalized_state(objective_instance $objinst) {
        $state = $objinst->get_type_state();
        return $state ?? (object) ['lb' => 0];
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
class not_access_platform_config_form_extender implements extender {

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded');
        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $mform->addElement('duration', 'cd_time', get_string('howmuchtime', 'block_gearup'),
            ['units' => [DAYSECS, WEEKSECS]]);
        $mform->setDefault('cd_time', WEEKSECS);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        return [];
    }

}
