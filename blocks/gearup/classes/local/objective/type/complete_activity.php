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
 * Complete activity.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\activity_completed;
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
 * Complete activity.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_activity implements type, type_with_state_initialisation, type_with_supporting_url, type_with_update_after_restore,
        has_availability_info {

    /** Not unique. */
    const UNIQUENESS_DISABLED = 0;
    /** Unique per course. */
    const UNIQUENESS_PER_COURSE = 1;
    /** Unique per section. */
    const UNIQUENESS_PER_SECTION = 2;

    /** Any activity. */
    const WHICH_ANY = 0;
    /** Any activity in the course. */
    const WHICH_ANY_IN_COURSE = 1;
    /** A specific activity, in the current course. */
    const WHICH_SPECIFIC_IN_COURSE = 2;

    /** URL mode to course module. */
    const URLMODE_CM = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {

        // TODO Test this!
        $userid = $missioninst->get_subject_id();
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $which = $config->which ?? static::WHICH_ANY;

        // We do not attempt to initialise the state when not tracking a specific course.
        $incourse = $which == static::WHICH_ANY_IN_COURSE || $which == static::WHICH_SPECIFIC_IN_COURSE;
        if (!$incourse) {
            return;
        }

        $modinfo = course_utils::get_modinfo($config->courseid, $userid);
        $completioninfo = course_utils::get_completion_info($modinfo);
        $counter = 0;
        $seen = [];

        // That's unexpected.
        if (!$modinfo || !$completioninfo) {
            return;
        }

        /** @var \cm_info[] */
        $cms = [];
        if ($which == static::WHICH_SPECIFIC_IN_COURSE) {
            try {
                $cm = $modinfo->get_cm($config->cmid);
                $cms[] = $cm;
            } catch (\moodle_exception $e) { // @codingStandardsIgnoreLine
            }
        } else {
            $cms = $modinfo->get_cms();
        }

        foreach ($cms as $cm) {
            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                continue;
            }
            $data = $completioninfo->get_data($cm, true, $userid);
            if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                $seen[] = ['courseid' => $cm->course, 'section' => $cm->sectionnum];
                $counter++;
            }
        }

        if ($counter > 0) {
            $instance->increment_counter($counter);
            if ($objective->get_count_needed() > 1) {
                $state = $this->get_normalized_state($instance);
                $state->seen = $seen;
                $instance->set_type_state($state);
            }
        }
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof activity_completed) {
            return false;
        }

        $objective = $instance->get_objective();
        $actiondata = $this->get_data_from_action($action);
        if (!$actiondata) {
            return false;
        }

        $courseid = $actiondata->courseid;
        $sectionnum = $actiondata->sectionnum;

        // Increment the counter.
        $instance->increment_counter(1);

        // Update the state if we track more than one. This is the safest way to
        // ensure that manual tracking never plays against us, and that uniqueness
        // for courses is also handled.
        if ($objective->get_count_needed() > 1) {
            $state = $this->get_normalized_state($instance);
            $state->seen[] = [
                'courseid' => $courseid,
                'section' => $sectionnum,
            ];
            $instance->set_type_state($state);
        }
    }

    public function get_availability_info(): info {
        return new completion_required_info();
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new complete_activity_config_form($mission);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecompleteactivity', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typecompleteactivitydesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_CM) {
            $config = $objective->get_type_config();
            $which = $config->which ?? static::WHICH_ANY;
            $cmid = $config->cmid ?? 0;
            $courseid = $config->courseid ?? 0;
            if ($which == static::WHICH_SPECIFIC_IN_COURSE && $courseid && $cmid) {
                return course_utils::get_cm_info($config->courseid, 0, $cmid)->url ?? null;
            }
        }
        return null;
    }

    /**
     * Get the data from an action.
     *
     * @param activity_completed $action The action.
     * @return object|null
     */
    protected function get_data_from_action(activity_completed $action) {
        $modinfo = course_utils::get_modinfo($action->get_course_id(), $action->get_user_id());
        $cm = course_utils::get_cm_info($modinfo, $action->get_user_id(), $action->get_context()->instanceid);
        $completioninfo = course_utils::get_completion_info($modinfo);

        if (!$modinfo || !$cm || !$completioninfo) {
            return null;
        }

        return (object) [
            'courseid' => $action->get_course_id(),
            'cmid' => (int) $cm->id,
            'cminfo' => $cm,
            'completioninfo' => $completioninfo,
            'sectionnum' => $cm->sectionnum,
            'modinfo' => $modinfo,
        ];
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
            $state = (object) ['seen' => []];
        }
        return $state;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof activity_completed;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        if (!$action instanceof activity_completed) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $which = $config->which;
        $courseid = $config->courseid ?? 0;
        $cmid = $config->cmid ?? null;
        $uniqueness = $config->uniqueness;
        $incourse = $which == static::WHICH_ANY_IN_COURSE || $which == static::WHICH_SPECIFIC_IN_COURSE;
        $actioncourseid = $action->get_course_id();

        // Validate that we are in the right course.
        if ($incourse && $actioncourseid != $courseid) {
            return false;
        }

        $actiondata = $this->get_data_from_action($action);
        if (!$actiondata) {
            return false;
        }
        $actioncmid = $actiondata->cmid;
        $actionsectionnum = $actiondata->sectionnum;

        // Validate that we're hitting the right course module.
        if ($which == static::WHICH_SPECIFIC_IN_COURSE) {
            if ($actioncmid != $cmid) {
                return false;
            }
        }

        // No uniqueness to check, bail.
        if ($uniqueness == static::UNIQUENESS_DISABLED) {
            return true;
        }

        // Validate the uniqueness.
        $state = $this->get_normalized_state($instance);
        $checkcourseid = $uniqueness == static::UNIQUENESS_PER_COURSE;
        $seenbefore = false;
        foreach ($state->seen as $seen) {
            if ($seen['courseid'] == $actioncourseid) {
                if ($checkcourseid) {
                    $seenbefore = true;
                    break;
                }
                if ($seen['section'] == $actionsectionnum) {
                    $seenbefore = true;
                    break;
                }
            }
        }
        if ($seenbefore) {
            return false;
        }

        return true;
    }

    public function update_after_restore(restore_context $restore, objective $objective, mission $mission) {
        if (!$objective instanceof persisted_objective) {
            $restore->get_logger()->process("Cannot process after_restore of objective " . $objective->get_id(),
                backup::LOG_WARNING);
            return;
        }

        $config = $objective->get_type_config();
        $courseid = $config->courseid ?? 0;
        $cmid = $config->cmid ?? null;
        $haschanged = false;

        if (empty($courseid) && empty($cmid)) {
            return;
        }

        // We can only guess the new course ID if it's the current course.
        if ($courseid == $restore->get_original_course_id()) {
            $config->courseid = $restore->get_course_id();
            $haschanged = true;
        }

        if ($cmid) {
            $newcmid = $restore->get_mapping_id('course_module', $cmid);
            if ($newcmid) {
                $config->cmid = $newcmid;
                $haschanged = true;
            }
        }
        if (!$haschanged) {
            return;
        }

        try {
            $objective->get_persistent()->set('configdata', $config);
            $objective->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating objective " . $objective->get_id(), backup::LOG_WARNING);
        }
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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_activity_config_form implements extender, extender_with_supporting_url_modes {

    protected $context;
    protected $coursecontext;
    protected $courseid;
    protected $incourse;
    protected $mission;
    protected $whichoptions;

    /**
     * Constructor.
     *
     * @param \context $context The mission's context.
     */
    public function __construct(mission $mission) {
        $this->context = $mission->get_context();
        $this->mission = $mission;

        $coursecontext = $this->context->get_course_context(false);
        $isfrontpage = ($coursecontext && $coursecontext->instanceid == SITEID);
        $this->incourse = $coursecontext && !$isfrontpage;
        $this->coursecontext = $this->incourse ? $coursecontext : null;
        $this->courseid = $this->incourse ? $this->coursecontext->instanceid : null;

        $options = [];
        if ($this->incourse) {
            $options[] = complete_activity::WHICH_ANY_IN_COURSE;
            $options[] = complete_activity::WHICH_SPECIFIC_IN_COURSE;
        } else {
            $options[] = complete_activity::WHICH_ANY;
        }
        $this->whichoptions = $options;
    }

    public function definition($mform): array {
        $els = [];
        $incourse = $this->incourse;

        $whichoptions = $this->get_which_options_for_select();
        $els[] = $mform->addElement('select', 'cd_which', get_string('elligibleactivity', 'block_gearup'), $whichoptions);
        $mform->setDefault('cd_which', key($whichoptions));

        if (count($whichoptions) <= 1) {
            $firstkey = key($whichoptions);
            reset($whichoptions);
            $mform->setConstant('cd_which', $firstkey);
            $mform->freeze('cd_which');
        }

        if ($incourse) {
            $els[] = $mform->addElement('hidden', 'cd_courseid', $this->courseid);
            $mform->setType('cd_courseid', PARAM_INT);
            $mform->setConstant('cd_courseid', $this->courseid);

            $modinfo = course_utils::get_modinfo($this->courseid);
            $sections = $modinfo ? $modinfo->get_sections() : [];

            $options = [];
            foreach ($sections as $sectionnum => $cmids) {
                $modules = [];
                foreach ($cmids as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if ($cm->completion == COMPLETION_TRACKING_NONE) {
                        continue;
                    }
                    $modules[$cmid] = $cm->name;
                }
                $options['#' . $sectionnum . ': ' . get_section_name($this->courseid, $sectionnum)] = $modules;
            }
            $els[] = $mform->addElement('selectgroups', 'cd_cmid', get_string('chosenactivity', 'block_gearup'), $options);

            $mform->hideIf('cd_cmid', 'cd_which', 'neq', complete_activity::WHICH_SPECIFIC_IN_COURSE);
        }

        // Tweak the count needed field.
        $mform->hideIf('countneeded', 'cd_which', 'eq', complete_activity::WHICH_SPECIFIC_IN_COURSE);
        $el = $mform->addElement($mform->removeElement('countneeded', true));
        $el->setLabel(get_string('numberactivitytocomplete', 'block_gearup'));
        $el->_helpbutton = '';
        $els[] = $el;

        if (!$incourse) {
            $els[] = $mform->addElement('select', 'cd_uniqueness', get_string('counts', 'block_gearup'), [
                complete_activity::UNIQUENESS_DISABLED => get_string('everytime', 'block_gearup'),
                complete_activity::UNIQUENESS_PER_COURSE => get_string('oncepercourse', 'block_gearup'),
                complete_activity::UNIQUENESS_PER_SECTION => get_string('oncepersection', 'block_gearup'),
            ]);
            $mform->hideIf('cd_uniqueness', 'cd_which', 'neq', complete_activity::WHICH_ANY);
        } else {
            $els[] = $mform->addElement('select', 'cd_uniqueness', get_string('counts', 'block_gearup'), [
                complete_activity::UNIQUENESS_DISABLED => get_string('everytime', 'block_gearup'),
                complete_activity::UNIQUENESS_PER_SECTION => get_string('oncepersection', 'block_gearup'),
            ]);
            $mform->hideIf('cd_uniqueness', 'cd_which', 'neq', complete_activity::WHICH_ANY_IN_COURSE);
        }

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [complete_activity::URLMODE_CM],
            'cd_which',
            [complete_activity::WHICH_SPECIFIC_IN_COURSE],
        ]);

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == complete_activity::WHICH_SPECIFIC_IN_COURSE) {
            $data->cd_uniqueness = complete_activity::UNIQUENESS_DISABLED;
        }
        return $data;
    }

    public function get_supporting_url_modes(): array {
        if (!in_array(complete_activity::WHICH_SPECIFIC_IN_COURSE, $this->whichoptions)) {
            return [];
        }
        return [
            complete_activity::URLMODE_CM => get_string('activitypage', 'block_gearup'),
        ];
    }

    /**
     * Get the options.
     *
     * @return array
     */
    protected function get_which_options() {
        return array_reduce($this->whichoptions, function($carry, $value) {
            $label = null;
            switch ($value) {
                case complete_activity::WHICH_ANY:
                    $label = get_string('anyactivity', 'block_gearup');
                    $description = '';
                    break;

                case complete_activity::WHICH_ANY_IN_COURSE:
                    $shortname = $this->coursecontext->get_context_name(false, true);
                    $label = get_string('anyactivityinthiscourse', 'block_gearup', $shortname);
                    $description = '';
                    break;

                case complete_activity::WHICH_SPECIFIC_IN_COURSE:
                    $label = get_string('specificone', 'block_gearup');
                    $description = '';
                    break;
            }

            if ($label) {
                $carry[$value] = [
                    'value' => $value,
                    'label' => $label,
                    'description' => $description,
                ];
            }

            return $carry;
        }, []);
    }

    /**
     * Get the options.
     *
     * @return array
     */
    protected function get_which_options_for_select() {
        return array_map(function($option) {
            return $option['label'];
        }, $this->get_which_options());
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
