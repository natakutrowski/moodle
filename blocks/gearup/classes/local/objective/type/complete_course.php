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
 * Complete course.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\course_completed;
use block_gearup\local\availability\completion_required_info;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\form_utils;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Complete course.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_course implements type, type_with_state_initialisation, type_with_supporting_url, type_with_update_after_restore,
        has_availability_info {

    /** Any course. */
    const WHICH_ANY = 0;
    /** One specific course. */
    const WHICH_SPECIFIC = 1;

    /** The URL mode. */
    const URLMODE_COURSE = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $which = $config->which;
        $courseid = $config->courseid ?? 0;

        if ($which != static::WHICH_SPECIFIC) {
            return false;
        }

        if ($this->is_course_completed($courseid, $missioninst->get_subject_id())) {
            $instance->increment_counter(1);
        }
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof course_completed) {
            return false;
        }
        $instance->increment_counter(1);
    }

    public function get_availability_info(): info {
        return new completion_required_info();
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new complete_course_config_form($mission->get_context());
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecompletecourse', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typecompletecoursedesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_COURSE) {
            $config = $objective->get_type_config();
            $which = $config->which ?? static::WHICH_ANY;
            $courseid = $config->courseid ?? 0;
            if ($which == static::WHICH_SPECIFIC && $courseid) {
                return new moodle_url('/course/view.php', ['id' => $courseid]);
            }
        }
        return null;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof course_completed;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        if (!$action instanceof course_completed) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $which = $config->which;
        $courseid = $config->courseid ?? 0;

        if ($which == static::WHICH_SPECIFIC) {
            return $courseid == $action->get_course_id();
        }

        return true;
    }

    public function is_course_completed($courseid, $userid) {
        $completion = course_utils::get_completion_info($courseid);
        return $completion->is_course_complete($userid);
    }

    public function update_after_restore(restore_context $restore, objective $objective, mission $mission) {
        if (!$objective instanceof persisted_objective) {
            $restore->get_logger()->process("Cannot process after_restore of objective " . $objective->get_id(),
                backup::LOG_WARNING);
            return;
        }

        $config = $objective->get_type_config();
        $courseid = $config->courseid ?? 0;
        if (empty($courseid)) {
            return;
        }

        // If the original course ID referenced is not the one from the backup, then keep the existing data.
        $newcourseid = $restore->get_course_id();
        if ($courseid != $restore->get_original_course_id()) {
            return;
        }

        try {
            $config->courseid = $newcourseid;
            $objective->get_persistent()->set('configdata', $config);
            $objective->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating objective " . $objective->get_id(), backup::LOG_WARNING);
        }
    }

}


/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_course_config_form implements extender, extender_with_supporting_url_modes {

    protected $context;
    protected $coursecontext;
    protected $courseid;
    protected $incourse;

    /**
     * Constructor.
     *
     * @param \context $context The mission's context.
     */
    public function __construct(\context $context) {
        $this->context = $context;

        $coursecontext = $this->context->get_course_context(false);
        $isfrontpage = ($coursecontext && $coursecontext->instanceid == SITEID);
        $this->incourse = $coursecontext && !$isfrontpage;
        $this->coursecontext = $this->incourse ? $coursecontext : null;
        $this->courseid = $this->incourse ? $this->coursecontext->instanceid : null;
    }

    public function definition($mform): array {
        $els = [];

        $incourse = $this->incourse;

        if ($incourse) {
            $els[] = $mform->addElement('hidden', 'cd_which', complete_course::WHICH_SPECIFIC);
            $mform->setType('cd_which', PARAM_INT);
            $mform->setConstant('cd_which', complete_course::WHICH_SPECIFIC);

            $els[] = $mform->addElement('hidden', 'cd_courseid', $this->courseid);
            $mform->setType('cd_courseid', PARAM_INT);
            $mform->setConstant('cd_courseid', $this->courseid);

            $els[] = $mform->addElement('static', 'cd_courseidhint', get_string('course', 'core'),
                get_string('thiscoursen', 'block_gearup', $this->coursecontext->get_context_name(false, true)));

            $mform->removeElement('countneeded', true);
            $els[] = $mform->addElement('hidden', 'countneeded');
            $mform->setType('countneeded', PARAM_INT);
            $mform->setConstant('countneeded', 1);

        } else {
            $els[] = $mform->addElement('select', 'cd_which', get_string('elligiblecourse', 'block_gearup'), [
                complete_course::WHICH_ANY => get_string('anycourse', 'block_gearup'),
                complete_course::WHICH_SPECIFIC => get_string('specificcourse', 'block_gearup'),
            ]);

            $els[] = $mform->addElement('course', 'cd_courseid', get_string('choosecourse', 'block_gearup'));
            $mform->hideIf('cd_courseid', 'cd_which', 'neq', complete_course::WHICH_SPECIFIC);

            $els[] = $mform->addElement($mform->removeElement('countneeded', true));
            $mform->hideIf('countneeded', 'cd_which', 'eq', complete_course::WHICH_SPECIFIC);
        }

        if ($mform->elementExists('countneeded')) {
            $countneededel = $mform->getElement('countneeded');
            $countneededel->setLabel(get_string('numbercoursetocomplete', 'block_gearup'));
        }

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [complete_course::URLMODE_COURSE],
            'cd_which',
            [complete_course::WHICH_SPECIFIC],
        ]);

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == complete_course::WHICH_SPECIFIC) {
            $data->cd_courseid = (int) $data->cd_courseid;
            $data->countneeded = 1;
        } else {
            unset($data->cd_courseid);
        }
        $data->cd_which = (int) $data->cd_which;
        $data->countneeded = (int) $data->countneeded;
        return $data;
    }

    public function get_supporting_url_modes(): array {
        if ($this->incourse) {
            return [];
        }
        return [
            complete_course::URLMODE_COURSE => get_string('coursepage', 'block_gearup'),
        ];
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        if (($data->cd_which ?? null) == complete_course::WHICH_SPECIFIC && empty($data->cd_courseid)) {
            $errors['cd_courseid'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
