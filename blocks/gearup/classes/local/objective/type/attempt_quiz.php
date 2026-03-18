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
 * Attempt quiz.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\quiz_attempted;
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

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Attempt quiz.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_quiz implements type, type_with_supporting_url, type_with_update_after_restore {

    /** Any activity. */
    const WHICH_ANY = 0;
    /** Any activity in the course. */
    const WHICH_ANY_IN_COURSE = 1;
    /** A specific activity, in the current course. */
    const WHICH_SPECIFIC_IN_COURSE = 2;

    /** URL mode to course module. */
    const URLMODE_CM = 1;

    /** No uniqueness. */
    const UNIQUENESS_DISABLED = 0;
    /** Unique per module. */
    const UNIQUENESS_PER_CM = 1;

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof quiz_attempted) {
            return false;
        }

        $objective = $instance->get_objective();
        $setup = $this->get_setup($objective);

        // Increment the counter.
        $instance->increment_counter(1);

        // Save for the uniqueness evaluator.
        if ($setup->checkuniqueness) {
            $uniquenessevaluator = $this->get_uniqueness_evaluator($setup);

            if ($uniquenessevaluator) {
                $state = $this->get_normalized_state($instance);

                $uniquenessevaluator->load_state($state->uq);
                $values = [
                    'courseid' => $action->get_course_id(),
                    'cmid' => $action->get_cm_id(),
                ];
                $uniquenessevaluator->add_entry($values);
                $state->uq = $uniquenessevaluator->get_state();

                $instance->set_type_state($state);
            }
        }
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new attempt_quiz_config_form($mission);
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeattemptquiz', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typeattemptquizdesc', 'block_gearup');
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
     * Get the state.
     *
     * @param objective_instance $instance The instance.
     * @return object
     */
    protected function get_normalized_state(objective_instance $instance) {
        $state = $instance->get_type_state();
        return $state ?? (object) ['uq' => []];
    }

    /**
     * Get the setup.
     *
     * I don't know that it was a good idea to have this method...
     *
     * @param objective $objective The objective.
     * @return object
     */
    protected function get_setup(objective $objective) {
        $config = $objective->get_type_config();
        $config->which = $config->which ?? static::WHICH_ANY;
        $config->courseid = $config->courseid ?? 0;
        $config->cmid = $config->cmid ?? null;
        $config->uniqueness = $config->uniqueness ?? static::UNIQUENESS_DISABLED;
        $config->mingrade = $config->mingrade ?? null;

        $config->checkuniqueness = $config->uniqueness != static::UNIQUENESS_DISABLED
            && $config->which != static::WHICH_SPECIFIC_IN_COURSE
            && $objective->get_count_needed() > 1;

        return $config;
    }

    /**
     * Get the uniqueness evaluator.
     *
     * @param object $setup The setup.
     */
    protected function get_uniqueness_evaluator($setup) {
        if (!$setup->checkuniqueness || $setup->uniqueness != static::UNIQUENESS_PER_CM) {
            return null;
        }
        return new basic_uniqueness_evaluator(['courseid', 'cmid']);
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof quiz_attempted;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        if (!$action instanceof quiz_attempted) {
            return false;
        }

        $objective = $instance->get_objective();
        $setup = $this->get_setup($objective);

        $maybeqa = null;
        $getqa = function () use (&$maybeqa, $action) {
            if (!$maybeqa) {
                $maybeqa = $action->get_quiz_attempt();
            }
            return $maybeqa;
        };

        // Check we are in the right module.
        if ($setup->which == static::WHICH_SPECIFIC_IN_COURSE && $setup->cmid != $action->get_cm_id()) {
            return false;
        }

        // Evaluate uniqueness.
        $uniquenessevaluator = $this->get_uniqueness_evaluator($setup);
        if ($uniquenessevaluator) {
            $uniquenessevaluator->load_state($this->get_normalized_state($instance)->uq);
            $checkvalues = [
                'courseid' => $action->get_course_id(),
                'cmid' => $action->get_cm_id(),
            ];
            if ($uniquenessevaluator->has_happened_before($checkvalues)) {
                return false;
            }
        }

        // Evaluate the grade, out of a 100.
        $qa = null;
        $mingrade = 0;
        if (!empty($setup->mingrade)) {
            if (substr($setup->mingrade, -1) === '%') {
                $mingrade = (int) substr($setup->mingrade, 0, -1);
            } else if ($setup->mingrade === 'gradepass') {
                // We will probably want to improve this in the future, but at the moment it is very
                // unlikely that we will need to fetch the passing grade of the same quiz multiple times in
                // the same request. That can maybe happened during cron for unfinished attempts. We could
                // improve this in the future when grading is deferred. Maybe this can happen if we have
                // multiple objectives to evaluate in the same context.
                $gradeitems = \grade_item::fetch_all([
                    'courseid' => $getqa()->get_courseid(),
                    'itemtype' => 'mod',
                    'itemmodule' => 'quiz',
                    'iteminstance' => $getqa()->get_quizid(),
                    'itemnumber' => 0,
                    'outcomeid' => null, // We must exclude them.
                ]);
                $gradeitem = reset($gradeitems);
                if (count($gradeitems) > 1) {
                    debugging('Unexpected number of grade items obtained for quiz.', DEBUG_DEVELOPER);
                }
                if ($gradeitem && $gradeitem->gradetype == GRADE_TYPE_VALUE && $gradeitem->gradepass) {
                    $mingrade = $gradeitem->grademax > 0 ? $gradeitem->gradepass / $gradeitem->grademax * 100 : 0;
                }
            }
            $mingrade = grade_floatval($mingrade);
        }
        if (grade_floats_different($mingrade, 0)) {
            $qa = $getqa();
            $rawgrade = $qa->get_sum_marks();

            // We cannot accept an attempt that requires manual grading. In the future we can decide to capture
            // the event attempt_manual_grading_completed however as of Moodle 4.4, the latter is not guaranteed
            // to be broadcasted as it depends on notification settings. We can also observe question_manually_graded
            // but it could be noisy. For now, we will not support manual grading when a min grade is required.
            if ($qa->requires_manual_grading() || !$rawgrade || !grade_floats_different($rawgrade, 0)) {
                return false;
            }

            // Checking that the grade is greater than 0, just making sure that we don't use a scale
            // as a negative grade is a scale. Also we do not want divisions by zero.s
            $quiz = $qa->get_quiz();
            if ($quiz->grade > 0) {
                $quizgrade = $rawgrade * $quiz->grade / $quiz->sumgrades;
                $gradeoutof100 = $quizgrade / ($quiz->grade / 100);
                $grade = grade_floatval($gradeoutof100);
                if ($grade < $mingrade) {
                    return false;
                }
            }
        }

        return true;
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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_quiz_config_form implements extender, extender_with_supporting_url_modes {

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
            $options[] = attempt_quiz::WHICH_ANY_IN_COURSE;
            $options[] = attempt_quiz::WHICH_SPECIFIC_IN_COURSE;
        } else {
            $options[] = attempt_quiz::WHICH_ANY;
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
                    if ($cm->modname !== 'quiz') {
                        continue;
                    }
                    $modules[$cmid] = $cm->name;
                }
                if (!empty($modules)) {
                    $options['#' . $sectionnum . ': ' . get_section_name($this->courseid, $sectionnum)] = $modules;
                }
            }
            if (empty($options)) {
                $options = [get_string('error', 'core') => [get_string('noresults', 'core')]];
            }
            $els[] = $mform->addElement('selectgroups', 'cd_cmid', get_string('chosenactivity', 'block_gearup'), $options);

            $mform->hideIf('cd_cmid', 'cd_which', 'neq', attempt_quiz::WHICH_SPECIFIC_IN_COURSE);
        }

        // Tweak the count needed field.
        $el = $mform->addElement($mform->removeElement('countneeded', true));
        $el->setLabel(get_string('attemptsrequired', 'block_gearup'));
        $mform->addHelpButton('countneeded', 'attemptsrequired', 'block_gearup');
        $els[] = $el;

        $els[] = $mform->addElement('advcheckbox', 'cd_uniquenessdisabled', get_string('allowmultipleperquiz', 'block_gearup'));
        $mform->hideIf('cd_uniquenessdisabled', 'cd_which', 'eq', attempt_quiz::WHICH_SPECIFIC_IN_COURSE);
        $mform->addHelpButton('cd_uniquenessdisabled', 'allowmultipleperquiz', 'block_gearup');

        $els[] = $mform->addElement('select', 'cd_mingrade', get_string('graderequirement', 'block_gearup'), [
            '0' => get_string('anygrade', 'block_gearup'),
            'gradepass' => get_string('gradepass', 'grades'),
            '25%' => '25%',
            '40%' => '40%',
            '50%' => '50%',
            '60%' => '60%',
            '70%' => '70%',
            '75%' => '75%',
            '80%' => '80%',
            '85%' => '85%',
            '90%' => '90%',
            '95%' => '95%',
            '100%' => '100%',
        ]);
        $mform->addHelpButton('cd_mingrade', 'graderequirement', 'block_gearup');
        $mform->setDefault('cd_mingrade', 'gradepass');

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [attempt_quiz::URLMODE_CM],
            'cd_which',
            [attempt_quiz::WHICH_SPECIFIC_IN_COURSE],
        ]);

        return $els;
    }

    public function get_data($data) {

        // Default to unique per cm, except when using a specific quiz or uniqueness is not required.
        $data->cd_uniqueness = attempt_quiz::UNIQUENESS_PER_CM;
        if ($data->cd_which == attempt_quiz::WHICH_SPECIFIC_IN_COURSE || !empty($data->cd_uniquenessdisabled)) {
            $data->cd_uniqueness = attempt_quiz::UNIQUENESS_DISABLED;
        }
        unset($data->cd_uniquenessdisabled);

        // cast to int.
        if (isset($data->cd_which)) {
            $data->cd_which = (int) $data->cd_which;
        }
        if (isset($data->cd_courseid)) {
            $data->cd_courseid = (int) $data->cd_courseid;
        }
        if (isset($data->cd_cmid)) {
            $data->cd_cmid = (int) $data->cd_cmid;
        }

        return $data;
    }

    public function get_supporting_url_modes(): array {
        if (!in_array(attempt_quiz::WHICH_SPECIFIC_IN_COURSE, $this->whichoptions)) {
            return [];
        }
        return [
            attempt_quiz::URLMODE_CM => get_string('activitypage', 'block_gearup'),
        ];
    }

    /**
     * Get the options.
     *
     * @return array
     */
    protected function get_which_options() {
        return array_reduce($this->whichoptions, function ($carry, $value) {
            $label = null;
            switch ($value) {
                case attempt_quiz::WHICH_ANY:
                    $label = get_string('anyquiz', 'block_gearup');
                    $description = '';
                    break;

                case attempt_quiz::WHICH_ANY_IN_COURSE:
                    $shortname = $this->coursecontext->get_context_name(false, true);
                    $label = get_string('anyquizinthiscourse', 'block_gearup', $shortname);
                    $description = '';
                    break;

                case attempt_quiz::WHICH_SPECIFIC_IN_COURSE:
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
        return array_map(function ($option) {
            return $option['label'];
        }, $this->get_which_options());
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        if (($data->cd_which ?? null) == attempt_quiz::WHICH_SPECIFIC_IN_COURSE
                && empty($data->cd_cmid)
        ) {
            $errors['cd_cmid'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}

/**
 * Evaluator.
 *
 * I do not really like this implementation, that's why I left it there. I think
 * we should be more resilient to change in configuration, where the state could
 * potentially mismatch the config.
 *
 * I also think we should be giving the values once, and be able to tell whether
 * they happened before or not, and then calling `add_entry` to add the values
 * we have given. Maybe we need two classes, the evaluator and the state manager.
 *
 * Maybe I'm over thinking this...
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class basic_uniqueness_evaluator {

    /** @var string[] The keys used for uniqueness, ordered. */
    protected $keys;
    /** @var int The nubmer of keys. */
    protected $nkeys;
    /** @var array[] An array of keyed values. */
    protected $state;

    /**
     * Constructor.
     *
     * @param string[] $keys The keys.
     */
    public function __construct(array $keys) {
        $this->keys = $keys;
        $this->nkeys = count($keys);
        $this->state = [];
    }

    /**
     * Add entry.
     *
     * @param object|array $values Of key'd values.
     * @return void
     */
    public function add_entry($values): void {
        $values = (array) $values;
        if (!$this->validate_values($values)) {
            debugging('Could not validate the values, skipping adding entry.', DEBUG_DEVELOPER);
            return;
        }

        $finalvalues = array_intersect_key($values, array_flip($this->keys));
        $this->state[] = $finalvalues;
    }

    /**
     * Exports the state to be loaded back in.
     *
     * @return array
     */
    public function get_state(): array {
        return $this->state;
    }

    /**
     * Whether this has happened before.
     *
     * @param object|array $values Of key'd values.
     * @return bool
     */
    public function has_happened_before($values): bool {
        $values = (array) $values;
        if (!$this->validate_values($values)) {
            debugging('Could not validate the values, assessment failed.', DEBUG_DEVELOPER);
            return false;
        }

        foreach ($this->state as $state) {
            $nmatches = 0;
            foreach ($this->keys as $key) {
                if (!array_key_exists($key, $values) || !array_key_exists($key, $state)) {
                    break;
                }
                if ($values[$key] == $state[$key]) {
                    $nmatches++;
                }
            }
            if ($nmatches === $this->nkeys) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load a state in.
     *
     * @param array|object|null $state The state.
     * @return void
     */
    public function load_state($state = null): void {
        $this->state = (array) ($state ?? []);
    }

    /**
     * Validate that the values contain what we expect.
     *
     * @param array|object $values The key'd values.
     * @return bool
     */
    protected function validate_values($values): bool {
        foreach ($this->keys as $key) {
            if (!array_key_exists($key, $values)) {
                return false;
            } else if (!is_scalar($values[$key]) && !is_null($values[$key])) {
                return false;
            }
        }
        return true;
    }

}
