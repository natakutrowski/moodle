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
 * Access course.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\local\action\action;
use block_gearup\local\action\course_accessed;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\form\extender_with_supporting_url_modes;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;
use block_gearup\local\time\frequency_evaluator;
use block_gearup\local\utils\form_utils;
use block_gearup\local\utils\time_utils;
use DateTimeImmutable;
use lang_string;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Access course.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_course implements type, type_with_state_reevaluation, type_with_supporting_url, type_with_update_after_restore {

    /** Any course. */
    const WHICH_ANY = 0;
    /** One specific course. */
    const WHICH_SPECIFIC = 1;

    /** The URL mode. */
    const URLMODE_COURSE = 1;

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof course_accessed) {
            return;
        }

        $evaluator = $this->get_evaluator($action->get_time(), $instance);

        // When we're late, we must first reset the state.
        if ($evaluator->is_late()) {
            $this->reset_state($instance);
        }

        $config = $instance->get_objective()->get_type_config();
        $state = $this->get_normalized_state($instance->get_type_state());
        $state->lastaccess = $action->get_time()->getTimestamp();
        if (!empty($config->unique)) {
            $state->courseids[] = $action->get_course_id();
        }

        $instance->increment_counter(1);
        $instance->set_type_state($state);
        $instance->set_dormant_until($evaluator->get_dormant_until());
        $instance->set_stale_from($evaluator->get_stale_from());
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new access_course_config_form_extender($mission->get_context());
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typeaccesscourse', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new \lang_string('typeaccesscoursedesc', 'block_gearup');
    }

    public function get_supporting_url(objective $objective, $urltype): ?moodle_url {
        if ($urltype === static::URLMODE_COURSE) {
            $config = $objective->get_type_config();
            $which = $config->which;
            $courseid = $config->courseid ?? 0;
            if ($which == static::WHICH_SPECIFIC && $courseid) {
                return new moodle_url('/course/view.php', ['id' => $courseid]);
            }
        }
        return null;
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof course_accessed;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {
        if (!$action instanceof course_accessed) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $which = $config->which;
        $mode = $config->mode ?? frequency_evaluator::MODE_NONE;
        $unique = $config->unique ?? false;
        $courseid = $config->courseid ?? 0;

        // Check if we are in the specific course.
        if ($which == static::WHICH_SPECIFIC) {
            return $courseid == $action->get_course_id();
        }

        // Check for uniqueness.
        if ($unique) {
            $state = $this->get_normalized_state($instance->get_type_state());
            if (in_array($action->get_course_id(), $state->courseids)) {
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

        // Reset the list of course accessed.
        $state->courseids = [];
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

    /**
     * Normalize the state.
     *
     * @param mixed $state The state.
     * @return object
     */
    protected function get_normalized_state($state) {
        return $state ?? (object) ['lastaccess' => 0, 'courseids' => []];
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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_course_config_form_extender implements extender, extender_with_supporting_url_modes {

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
            $els[] = $mform->addElement('hidden', 'cd_which', access_course::WHICH_SPECIFIC);
            $mform->setType('cd_which', PARAM_INT);
            $mform->setConstant('cd_which', access_course::WHICH_SPECIFIC);

            $els[] = $mform->addElement('hidden', 'cd_courseid', $this->courseid);
            $mform->setType('cd_courseid', PARAM_INT);
            $mform->setConstant('cd_courseid', $this->courseid);

            $els[] = $mform->addElement('static',
                'cd_courseidhint',
                get_string('course', 'core'),
                get_string('thiscoursen', 'block_gearup', $this->coursecontext->get_context_name(false, true))
            );

        } else {
            $els[] = $mform->addElement('select', 'cd_which', get_string('elligiblecourse', 'block_gearup'), [
                access_course::WHICH_ANY => get_string('anycourse', 'block_gearup'),
                access_course::WHICH_SPECIFIC => get_string('specificcourse', 'block_gearup'),
            ]);

            $els[] = $mform->addElement('course', 'cd_courseid', get_string('choosecourse', 'block_gearup'));
            $mform->hideIf('cd_courseid', 'cd_which', 'neq', access_course::WHICH_SPECIFIC);
        }

        $els[] = $mform->addElement($mform->removeElement('countneeded', true));
        if ($mform->elementExists('countneeded')) {
            $el = $mform->getElement('countneeded');
            $el->setLabel(get_string('howmanytimes', 'block_gearup'));
        }

        if (!$incourse) {
            $els[] = $mform->addElement('advcheckbox', 'cd_unique', get_string('onlycountsoncepercourse', 'block_gearup'));
            $mform->hideIf('cd_unique', 'cd_which', 'neq', access_course::WHICH_ANY);
        }

        $els[] = $mform->addElement('select',
            'cd_mode',
            get_string('accesscounts', 'block_gearup'),
            frequency_evaluator::get_form_options()
        );

        $els[] = form_utils::add_js_amd_call($mform, 'block_gearup/form', 'disableOptionsWhenFieldNotEquals', [
            $mform->getAttribute('id'),
            'supportingurlmode',
            [access_course::URLMODE_COURSE],
            'cd_which',
            [access_course::WHICH_SPECIFIC],
        ]);

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == access_course::WHICH_SPECIFIC) {
            $data->cd_courseid = (int) $data->cd_courseid;
        } else {
            $data->cd_unique = (int) !empty($data->cd_unique);
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
            access_course::URLMODE_COURSE => get_string('coursepage', 'block_gearup'),
        ];
    }

    public function validation($data, $files) {
        $errors = [];
        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        if (($data->cd_which ?? null) == access_course::WHICH_SPECIFIC && empty($data->cd_courseid)) {
            $errors['cd_courseid'] = get_string('invaliddata', 'core_error');
        }
        return $errors;
    }

}
