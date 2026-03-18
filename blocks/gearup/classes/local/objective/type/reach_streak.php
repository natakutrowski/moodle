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

namespace block_gearup\local\objective\type;

use block_gearup\di;
use block_gearup\local\action\action;
use block_gearup\local\action\challenge_completed;
use block_gearup\local\action\streak_reached;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\mission\streak;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\repository\mission_instance_query;
use context_system;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Objective.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reach_streak implements type, type_with_state_initialisation {

    /** Any. */
    const WHICH_ANY = 0;
    /** Any in context. */
    const WHICH_ANY_IN_CONTEXT = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $repo = di::get('repository');
        $objective = $instance->get_objective();
        $context = $missioninst->get_mission()->get_context();

        $config = $this->get_normalised_config($objective);
        $which = $config->which;
        $issystem = $context instanceof context_system;
        $isanyincontext = $which == static::WHICH_ANY_IN_CONTEXT;

        $hasreached = false;
        try {
            // Any not ended streak in the relevant context.
            $query = new mission_instance_query($missioninst->get_mission()->get_context());
            $query->set_subject_id($missioninst->get_subject_id());
            $query->set_mission_type(streak::class);
            $query->set_mission_state(streak::STATE_ACTIVE);
            $query->filter_by_status('not_ended');
            $query->filter_by_counter_gte($config->streak);
            if ($isanyincontext) {
                $query->set_context_id($context->id);
            } else if (!$issystem) {
                // If we're in a course, we lookup each parent context. If we're in the system, we look everywhere.
                $query->filter_by_context_ids($context->get_parent_context_ids(true));
            }
            $hasreached = $repo->has_any_instances($query);
        } catch (\moodle_exception $e) {
            return false;
        }

        if ($hasreached) {
            $instance->increment_counter(1);
        }
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof streak_reached) {
            return false;
        }
        $instance->increment_counter(1);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new reach_streak_config_form($mission, di::get('repository'));
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typereachstreak', 'block_gearup');
    }

    protected function get_normalised_config(objective $objective): \stdClass {
        $config = $objective->get_type_config();
        $config->which = (int) ($config->which ?? static::WHICH_ANY);
        $config->streak = (int) $config->streak;
        return $config;
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typereachstreakdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof streak_reached;
    }

    public function is_action_passing_constraints(
        action $action,
        objective_instance $instance,
        mission_instance $missioninst
    ): bool {

        if (!$action instanceof streak_reached) {
            return false;
        } else if ($action->get_streak() < 1) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $this->get_normalised_config($objective);
        $issystem = $missioninst->get_mission()->get_context() instanceof context_system;

        if ($action->get_streak() < $config->streak) {
            return false;
        }

        if ($config->which == static::WHICH_ANY_IN_CONTEXT) {
            $contextid = $missioninst->get_mission()->get_context()->id;
            return $contextid == $action->get_context()->id;
        } else if (!$issystem) {
            $contextids = $missioninst->get_mission()->get_context()->get_parent_context_ids(true);
            return in_array($action->get_context()->id, $contextids);
        }

        return true;
    }

}


/**
 * Config form extender.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reach_streak_config_form implements extender {

    protected $context;
    protected $mission;
    protected $repository;

    /**
     * Constructor.
     *
     * @param mission $context The mission's context.
     * @param object $repository The repository.
     */
    public function __construct(mission $mission, $repository) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
        $this->repository = $repository;
    }

    public function definition($mform): array {
        $els = [];

        $mform->removeElement('countneeded', true);
        $els[] = $mform->addElement('hidden', 'countneeded');
        $mform->setType('countneeded', PARAM_INT);
        $mform->setConstant('countneeded', 1);

        $els[] = $mform->addElement('text', 'cd_streak', get_string('numbertoreach', 'block_gearup'), ['size' => 5]);
        $mform->setType('cd_streak', PARAM_INT);
        $mform->setDefault('cd_streak', 5);

        $anyincontext = get_string('sitewideonly', 'block_gearup');
        if (!$this->context instanceof context_system) {
            $anyincontext = get_string('anyinthiscourse', 'block_gearup', $this->context->get_context_name(false, true));
        }

        $els[] = $mform->addElement('select', 'cd_which', get_string('elligiblestreaks', 'block_gearup'), [
            reach_streak::WHICH_ANY => get_string('anystreak', 'block_gearup'),
            reach_streak::WHICH_ANY_IN_CONTEXT => $anyincontext,
        ]);
        $mform->setDefault('cd_which', reach_streak::WHICH_ANY_IN_CONTEXT);

        return $els;
    }

    public function get_data($data) {
        $data->cd_which = (int) $data->cd_which;
        $data->cd_streak = (int) $data->cd_streak;
        $data->countneeded = 1;
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        if ($data->cd_streak <= 1) {
            $errors['cd_streak'] = get_string('valuecannotbelessthan', 'block_gearup', 2);
        }
        return $errors;
    }

}
