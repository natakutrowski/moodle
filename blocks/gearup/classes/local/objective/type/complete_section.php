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
 * Complete section.
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
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\availability\static_info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\course\section_completion_checker;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_default_data;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\utils\course_utils;
use block_gearup\local\utils\form_utils;
use completion_info;
use context;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Complete section.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class complete_section implements
    has_availability_info,
    has_availability_info_for_context,
    type,
    type_with_state_initialisation,
    type_with_supporting_url,
    type_with_update_after_restore {

    /** Not unique. */
    const UNIQUENESS_DISABLED = 0;
    /** Unique per course. */
    const UNIQUENESS_PER_COURSE = 1;

    /** Any section. */
    const WHICH_ANY = 0;
    /** Any section in the course. */
    const WHICH_ANY_IN_COURSE = 1;
    /** A specific section, in the current course. */
    const WHICH_SPECIFIC_SECTION_IN_COURSE = 2;

    /** URL mode section. */
    const URLMODE_SECTION = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {

        // TODO Test this!
        $userid = $missioninst->get_subject_id();
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $countneeded = $objective->get_count_needed();

        $whichsection = $config->whichsection;
        $courseid = $config->courseid ?? 0;
        $sectionnum = $config->sectionnum ?? null;
        $ignoreunavailable = (bool) ($config->ignoreunavailable ?? false);
        $incourse = $whichsection == static::WHICH_ANY_IN_COURSE || $whichsection == static::WHICH_SPECIFIC_SECTION_IN_COURSE;

        if (!$incourse) {
            return;
        }

        $modinfo = course_utils::get_modinfo($courseid, $userid);
        if (!$modinfo) {
            return;
        }
        $completioninfo = course_utils::get_completion_info($modinfo);
        if (!$completioninfo) {
            return;
        }

        // Identify which sections have been completed.
        $counter = 0;
        $seen = [];
        if ($sectionnum !== null) {
            if ($this->is_section_completed($modinfo, $completioninfo, $sectionnum, $ignoreunavailable)) {
                $counter = 1;
            }
        } else {
            foreach ($modinfo->get_sections() as $sectionnum => $cminfos) {
                if ($this->is_section_completed($modinfo, $completioninfo, $sectionnum, $ignoreunavailable)) {
                    $counter++;
                    $seen[] = ['courseid' => $courseid, 'section' => $sectionnum];
                }
                if ($counter >= $countneeded) {
                    break;
                }
            }
        }

        // When some sections were completed.
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
            return null;
        }
        $sectionnum = $actiondata->sectionnum;

        // Increment the counter.
        $instance->increment_counter(1);

        // Update the state if we track more than one. This is the safest way to
        // ensure that manual tracking never plays against us, and that uniqueness
        // for courses is also handled.
        if ($objective->get_count_needed() > 1) {
            $state = $this->get_normalized_state($instance);
            $state->seen[] = [
                'courseid' => $action->get_course_id(),
                'section' => $sectionnum,
            ];
            $instance->set_type_state($state);
        }
    }

    public function get_availability_info(): info {
        return new completion_required_info();
    }

    public function get_availability_info_for_context(context $context): info {
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            $format = course_utils::get_format($coursecontext->instanceid);
            if ($coursecontext && (!$format || !$format->uses_sections())) {
                return new static_info(false, [get_string('requirescourseformattousesections', 'block_gearup')]);
            }
        }
        return new static_info(true);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new config_form($mission->get_context());
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typecompletesection', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typecompletesectiondesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_SECTION) {
            $config = $objective->get_type_config();
            $which = $config->whichsection ?? static::WHICH_ANY;
            $sectionnum = $config->sectionnum ?? null;
            $courseid = $config->courseid ?? 0;
            if ($which == static::WHICH_SPECIFIC_SECTION_IN_COURSE && $courseid && $sectionnum !== null) {
                $format = course_utils::get_format($courseid);
                return $format ? $format->get_view_url($sectionnum, ['navigation' => true]) : null;
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

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        if (!$action instanceof activity_completed) {
            return false;
        }

        // TODO Ideally, we would want the system to be able to discard this before we retrieve the instance,
        // as such that we do not need to retrieve an instance when the section is not completed yet.
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $whichsection = $config->whichsection;
        $courseid = $config->courseid ?? 0;
        $sectionnum = $config->sectionnum ?? null;
        $uniqueness = $config->uniqueness;
        $incourse = $whichsection == static::WHICH_ANY_IN_COURSE || $whichsection == static::WHICH_SPECIFIC_SECTION_IN_COURSE;
        $ignoreunavailable = (bool) ($config->ignoreunavailable ?? false);
        $actioncourseid = $action->get_course_id();

        // Validate that we are in the right course.
        if ($incourse && $actioncourseid != $courseid) {
            return false;
        }

        $actiondata = $this->get_data_from_action($action);
        if (!$actiondata) {
            return false;
        }

        $modinfo = $actiondata->modinfo;
        $actionsectionnum = $actiondata->sectionnum;
        $completioninfo = $actiondata->completioninfo;

        // Validate that this course uses completion.
        if ($completioninfo->is_enabled() != COMPLETION_ENABLED) {
            return false;
        }

        // Validate that we're hitting the right section.
        if ($whichsection == static::WHICH_SPECIFIC_SECTION_IN_COURSE) {
            if ($actionsectionnum !== $sectionnum) {
                return false;
            }
        }

        // Validate the uniqueness, or manual tracking.
        $state = $this->get_normalized_state($instance);
        $ignoresectionnum = $uniqueness == self::UNIQUENESS_PER_COURSE;
        $seenbefore = false;
        foreach ($state->seen as $seen) {
            if ($seen['courseid'] == $actioncourseid) {
                if ($ignoresectionnum) {
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

        if (!$this->is_section_completed($modinfo, $completioninfo, $actionsectionnum, $ignoreunavailable)) {
            return false;
        }

        return true;
    }

    /**
     * Whether the section of cm is completed.
     *
     * @param \course_modinfo $modinfo The modinfo.
     * @param completion_info $completioninfo The completion info.
     * @param int $sectionnum The section number.
     * @param bool $ignoreunavailable Ignore unavailable.
     * @return bool
     */
    protected function is_section_completed($modinfo, $completioninfo, $sectionnum, bool $ignoreunavailable = false) {
        $checker = new section_completion_checker($modinfo->courseid, $modinfo->userid);
        $checker->set_modinfo($modinfo);
        $checker->set_completion_info($completioninfo);
        $checker->set_ignore_unavailable($ignoreunavailable);
        return $checker->is_completed($sectionnum);
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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_form implements extender, extender_with_default_data, extender_with_supporting_url_modes {

    protected $context;
    protected $coursecontext;
    protected $courseid;
    protected $incourse;
    protected $whichsectionoptions;

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
            $options[] = complete_section::WHICH_ANY_IN_COURSE;
            $options[] = complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE;
        } else {
            $options[] = complete_section::WHICH_ANY;
        }
        $this->whichsectionoptions = $options;
    }

    public function definition($mform): array {
        $els = [];

        $incourse = $this->incourse;

        $whichsectionoptions = $this->get_which_section_options();
        $els[] = $mform->addElement('select',
            'cd_whichsection',
            get_string('elligiblesection', 'block_gearup'),
            $whichsectionoptions
        );
        if (count($whichsectionoptions) <= 1) {
            $firstkey = key($whichsectionoptions);
            reset($whichsectionoptions);
            $mform->setConstant('cd_whichsection', $firstkey);
            $mform->freeze('cd_whichsection');
        }

        // TODO If the course has been deleted get_section_name wil fail. And modinfo could be empty.
        if ($incourse) {
            $els[] = $mform->addElement('hidden', 'cd_courseid', $this->courseid);
            $mform->setType('cd_courseid', PARAM_INT);
            $mform->setConstant('cd_courseid', $this->courseid);

            $modinfo = course_utils::get_modinfo($this->courseid);
            $sections = $modinfo ? $modinfo->get_section_info_all() : [];
            $els[] = $mform->addElement('select',
                'cd_sectionnum',
                get_string('choosesection', 'block_gearup'),
                array_map(function ($sectioninfo) {
                    return '#' . $sectioninfo->section . ': ' . get_section_name($this->courseid, $sectioninfo->section);
                },
                $sections)
            );
            $mform->hideIf('cd_sectionnum', 'cd_whichsection', 'neq', complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE);
        }

        // Tweak the count needed field.
        $mform->hideIf('countneeded', 'cd_whichsection', 'eq', complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE);
        $el = $mform->addElement($mform->removeElement('countneeded', true));
        $el->setLabel(get_string('numbersectiontocomplete', 'block_gearup'));
        $el->_helpbutton = '';
        $els[] = $el;

        if (!$incourse) {
            $els[] = $mform->addElement('advcheckbox', 'cd_uniqueness', get_string('onlycountsoncepercourse', 'block_gearup'));
            $mform->hideIf('uniqueness', 'cd_whichsection', 'neq', complete_section::WHICH_ANY);
        } else {
            $els[] = $mform->addElement('hidden', 'cd_uniqueness', complete_section::UNIQUENESS_DISABLED);
            $mform->setType('cd_uniqueness', PARAM_INT);
            $mform->setConstant('cd_uniqueness', complete_section::UNIQUENESS_DISABLED);
        }

        $els[] = $mform->addElement('select', 'cd_ignoreunavailable', get_string('sectioncompletewhen', 'block_gearup'), [
            0 => get_string('allactivitiesarecompleted', 'block_gearup'),
            1 => get_string('onlyaccessibleactivitiesarecompleted', 'block_gearup'),
        ]);
        $mform->addHelpButton('cd_ignoreunavailable', 'sectioncompletewhen', 'block_gearup');

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [complete_section::URLMODE_SECTION],
            'cd_whichsection',
            [complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE],
        ]);

        return $els;
    }

    public function get_default_data($data) {
        $data->cd_ignoreunavailable = empty($data->cd_ignoreunavailable) ? 0 : 1;
        return $data;
    }

    public function get_data($data) {
        if ($data->cd_whichsection == complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE) {
            $data->cd_uniqueness = complete_section::UNIQUENESS_DISABLED;
        } else if ($data->cd_whichsection == complete_section::WHICH_ANY_IN_COURSE) {
            $data->cd_uniqueness = complete_section::UNIQUENESS_DISABLED;
        }
        $data->cd_ignoreunavailable = !empty($data->cd_ignoreunavailable);
        return $data;
    }

    public function get_supporting_url_modes(): array {
        if (!$this->incourse) {
            return [];
        }
        return [
            complete_section::URLMODE_SECTION => get_string('sectionpage', 'block_gearup'),
        ];
    }

    /**
     * Get the options.
     *
     * @return array
     */
    protected function get_which_section_options() {
        return array_reduce($this->whichsectionoptions, function ($carry, $key) {
            $label = null;
            switch ($key) {
                case complete_section::WHICH_ANY:
                    $label = get_string('anysection', 'block_gearup');
                    break;

                case complete_section::WHICH_ANY_IN_COURSE:
                    $shortname = $this->coursecontext->get_context_name(false, true);
                    $label = get_string('anysectioninthiscourse', 'block_gearup', $shortname);
                    break;

                case complete_section::WHICH_SPECIFIC_SECTION_IN_COURSE:
                    $label = get_string('specificsection', 'block_gearup');
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
        if (!isset($data->cd_whichsection)) {
            $errors['cd_whichsection'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
