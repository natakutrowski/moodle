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
 * Access activity.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\activity_accessed;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\time\frequency_evaluator;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\form_utils;
use block_gearup\local\utils\time_utils;
use DateTimeImmutable;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Access activity.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_activity implements type, type_with_state_reevaluation, type_with_supporting_url, type_with_update_after_restore {

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

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof activity_accessed) {
            return;
        }

        $evaluator = $this->get_evaluator($action->get_time(), $instance);

        // When we're late, we must first reset the state.
        if ($evaluator->is_late()) {
            $this->reset_state($instance);
        }

        $objective = $instance->get_objective();
        $actiondata = $this->get_data_from_action($action);
        $courseid = $actiondata->courseid;
        $sectionnum = $actiondata->sectionnum;

        $state = $this->get_normalized_state($instance->get_type_state());
        $state->lastaccess = $action->get_time()->getTimestamp();

        if ($objective->get_count_needed() > 1) {
            $state->seen[] = [
                'courseid' => $courseid,
                'section' => $sectionnum,
            ];
        }

        $instance->increment_counter(1);
        $instance->set_dormant_until($evaluator->get_dormant_until());
        $instance->set_stale_from($evaluator->get_stale_from());
        $instance->set_type_state($state);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new access_activity_config_form_extender($mission->get_context());
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeaccessactivity', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new \lang_string('typeaccessactivitydesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_CM) {
            $config = $objective->get_type_config();
            $which = $config->which ?? static::WHICH_ANY;
            $cmid = $config->cmid ?? 0;
            $courseid = $config->courseid ?? 0;
            if ($which == static::WHICH_SPECIFIC_IN_COURSE && $courseid && $cmid) {
                return course_utils::get_cm_info($courseid, 0, $cmid)->url ?? null;
            }
        }
        return null;
    }

    /**
     * Get the data from an action.
     *
     * @param activity_accessed $action The action.
     * @return object
     */
    protected function get_data_from_action(activity_accessed $action) {
        $modinfo = get_fast_modinfo($action->get_course_id());
        $cm = $modinfo->get_cm($action->get_cm_id());
        return (object) [
            'courseid' => $action->get_course_id(),
            'cmid' => (int) $cm->id,
            'cminfo' => $cm,
            'sectionnum' => $cm->sectionnum,
            'modinfo' => $modinfo,
        ];
    }

    /**
     * Normalize the state.
     *
     * @param mixed $state The state.
     * @return object
     */
    protected function get_normalized_state($state) {
        return $state ?? (object) ['lastaccess' => 0, 'seen' => []];
    }


    public function is_action_compatible(action $action): bool {
        return $action instanceof activity_accessed;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        if (!$action instanceof activity_accessed) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $which = $config->which;
        $mode = $config->mode ?? frequency_evaluator::MODE_NONE;
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
        $actioncmid = $actiondata->cmid;
        $actionsectionnum = $actiondata->sectionnum;

        // Validate that we're hitting the right course module.
        if ($which == static::WHICH_SPECIFIC_IN_COURSE) {
            if ($actioncmid != $cmid) {
                return false;
            }
        }

        // Check if within time boundary.
        if ($mode != frequency_evaluator::MODE_NONE) {
            $evaluator = $this->get_evaluator($action->get_time(), $instance);
            if ($evaluator->is_early()) {
                return false;
            }
        }

        // No uniqueness to check, bail.
        if ($uniqueness == static::UNIQUENESS_DISABLED) {
            return true;
        }

        // Validate the uniqueness.
        $state = $this->get_normalized_state($instance->get_type_state());
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

    public function reevaluate_state(objective_instance $instance) {
        $evaluator = $this->get_evaluator(new DateTimeImmutable(), $instance);
        if ($evaluator->is_late()) {
            $this->reset_state($instance);
        }
    }

    /**
     * Reset the state.
     *
     * @param objective_instance $instance
     */
    protected function reset_state(objective_instance $instance) {
        $state = $this->get_normalized_state($instance->get_type_state());
        $instance->reset_counter();
        $instance->set_dormant_until(null);
        $instance->set_stale_from(null);

        // Reset the list of activity accessed.
        $state->seen = [];
        $instance->set_type_state($state);
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
        return new frequency_evaluator($mode, time_utils::make_datetime($state->lastaccess, $tz), $now);
    }

    public function update_after_restore(restore_context $restore, objective $objective, mission $mission) {
        if (!$objective instanceof persisted_objective) {
            $restore->get_logger()->process("Cannot process after_restore of objective " . $objective->get_id(),
                backup::LOG_WARNING
            );
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

}

/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_activity_config_form_extender implements extender, extender_with_supporting_url_modes {

    protected $context;
    protected $coursecontext;
    protected $courseid;
    protected $incourse;
    protected $whichoptions;

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

        $options = [];
        if ($this->incourse) {
            $options[] = access_activity::WHICH_ANY_IN_COURSE;
            $options[] = access_activity::WHICH_SPECIFIC_IN_COURSE;
        } else {
            $options[] = access_activity::WHICH_ANY;
        }
        $this->whichoptions = $options;
    }

    public function definition($mform): array {
        $els = [];

        $incourse = $this->incourse;

        $whichoptions = $this->get_which_options();
        $els[] = $mform->addElement('select',
            'cd_which',
            get_string('elligibleactivity', 'block_gearup'),
            $whichoptions
        );
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

            $modinfo = get_fast_modinfo($this->courseid);

            $options = [];
            foreach ($modinfo->get_sections() as $sectionnum => $cmids) {
                $modules = [];
                foreach ($cmids as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if (!$cm->get_url()) {
                        continue;
                    }
                    $modules[$cmid] = $cm->name;
                }
                $options['#' . $sectionnum . ': ' . get_section_name($this->courseid, $sectionnum)] = $modules;
            }
            $els[] = $mform->addElement('selectgroups', 'cd_cmid', get_string('chooseactivity', 'block_gearup'), $options);

            $mform->hideIf('cd_cmid', 'cd_which', 'neq', access_activity::WHICH_SPECIFIC_IN_COURSE);
        }

        // Tweak the count needed field.
        $mform->hideIf('countneeded', 'cd_which', 'eq', access_activity::WHICH_SPECIFIC_IN_COURSE);
        $els[] = $mform->addElement($mform->removeElement('countneeded', true));

        if (!$incourse) {
            $els[] = $mform->addElement('select', 'cd_uniqueness', get_string('accesscounts', 'block_gearup'), [
                access_activity::UNIQUENESS_DISABLED => get_string('everytime', 'block_gearup'),
                access_activity::UNIQUENESS_PER_COURSE => get_string('oncepercourse', 'block_gearup'),
                access_activity::UNIQUENESS_PER_SECTION => get_string('oncepersection', 'block_gearup'),
            ]);
            $mform->hideIf('cd_uniqueness', 'cd_which', 'neq', access_activity::WHICH_ANY);
        } else {
            $els[] = $mform->addElement('select', 'cd_uniqueness', get_string('accesscounts', 'block_gearup'), [
                access_activity::UNIQUENESS_DISABLED => get_string('everytime', 'block_gearup'),
                access_activity::UNIQUENESS_PER_SECTION => get_string('oncepersection', 'block_gearup'),
            ]);
            $mform->hideIf('cd_uniqueness', 'cd_which', 'neq', access_activity::WHICH_ANY_IN_COURSE);
        }

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [access_activity::URLMODE_CM],
            'cd_which',
            [access_activity::WHICH_SPECIFIC_IN_COURSE],
        ]);

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == access_activity::WHICH_SPECIFIC_IN_COURSE) {
            $data->cd_uniqueness = access_activity::UNIQUENESS_DISABLED;
        }
        return $data;
    }

    public function get_supporting_url_modes(): array {
        if (!in_array(access_activity::WHICH_SPECIFIC_IN_COURSE, $this->whichoptions)) {
            return [];
        }
        return [
            access_activity::URLMODE_CM => get_string('activitypage', 'block_gearup'),
        ];
    }

    /**
     * Get the options.
     *
     * @return array
     */
    protected function get_which_options() {
        return array_reduce($this->whichoptions, function ($carry, $key) {
            $label = null;
            switch ($key) {
                case access_activity::WHICH_ANY:
                    $label = get_string('anyactivity', 'block_gearup');
                    break;

                case access_activity::WHICH_ANY_IN_COURSE:
                    $shortname = $this->coursecontext->get_context_name(false, true);
                    $label = get_string('anyactivityinthiscourse', 'block_gearup', $shortname);
                    break;

                case access_activity::WHICH_SPECIFIC_IN_COURSE:
                    $label = get_string('specificone', 'block_gearup');
                    break;

            }

            if ($label) {
                $carry[$key] = $label;
            }

            return $carry;
        }, []);
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
