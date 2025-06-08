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
use block_gearup\local\action\activity_completed;
use block_gearup\local\availability\completion_required_info;
use block_gearup\local\availability\course_completion_required_info;
use block_gearup\local\availability\course_context_required_info;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\availability\info_stack;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use completion_info;
use context;

defined('MOODLE_INTERNAL') || die();

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_rate implements type, type_with_state_initialisation, has_availability_info, has_availability_info_for_context {

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $this->check_and_complete($instance, $missioninst);
    }

    protected function check_and_complete(objective_instance $instance, mission_instance $missioninst) {
        $context = $missioninst->get_mission()->get_context();
        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return;
        }

        $courseid = $coursecontext->instanceid;
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $rateneeded = $config->rate ?? 1;
        $currentrate = $this->get_completion_rate($courseid, $missioninst->get_subject_id());
        if ($rateneeded <= 0 || $currentrate < $rateneeded) {
            return;
        }

        $instance->increment_counter(1);
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        $this->check_and_complete($instance, $missioninst);
    }

    public function get_availability_info(): info {
        return new completion_required_info();
    }

    public function get_availability_info_for_context(context $context): info {
        return new info_stack([
            new course_context_required_info($context),
            new course_completion_required_info($context),
        ]);
    }

    /**
     * Get the completion rate in the course.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @return float Completion rate between 0 and 1, returns 0 if nothing is to be completed.
     */
    protected function get_completion_rate($courseid, $userid) {
        global $CFG;
        $this->require_libs();

        $modinfo = get_fast_modinfo($courseid, $userid);
        $completioninfo = new completion_info($modinfo->get_course());

        $cms = array_filter($modinfo->get_cms(), function($cm) {
            return !($cm->deletioninprogress ?? false);
        });

        $loadwholecourse = true;
        $cmswithcompletion = 0;
        $cmscompleted = 0;

        foreach ($cms as $cm) {
            $isenabled = $completioninfo->is_enabled($cm) != COMPLETION_TRACKING_NONE;
            if (!$isenabled) {
                continue;
            }
            $cmswithcompletion++;

            $deprecatedfourtharg = $CFG->branch >= 400 ? null : $modinfo;
            $data = $completioninfo->get_data($cm, $loadwholecourse, $modinfo->get_user_id(), $deprecatedfourtharg);
            $loadwholecourse = false;
            $iscompleted = $data->completionstate != COMPLETION_INCOMPLETE;
            if (!$iscompleted) {
                continue;
            }
            $cmscompleted++;
        }

        return $cmswithcompletion > 0 ? $cmscompleted / $cmswithcompletion : 0;
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new completion_rate_config_form();
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecompletionrate', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typecompletionratedesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof activity_completed;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        return true;
    }

    /**
     * Require the needed libraries.
     */
    protected function require_libs() {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
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
class completion_rate_config_form implements extender {

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded');
        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $increments = range(5, 100, 5);
        $options = array_reduce($increments, function($carry, $item) {
            $carry[(string) ($item / 100)] = $item . '%';
            return $carry;
        }, []);
        $els[] = $mform->addElement('select', 'cd_rate', get_string('completionraterequired', 'block_gearup'), $options);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        return $errors;
    }

}
