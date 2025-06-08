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
 * Finish quest.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\objective\type;

use backup;
use block_gearup\di;
use block_gearup\local\action\action;
use block_gearup\local\action\quest_finished;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\objective\objective;
use block_gearup\local\objective\objective_instance;
use block_gearup\local\objective\persisted_objective;

defined('MOODLE_INTERNAL') || die();

/**
 * Finish quest.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class finish_quest implements type, type_with_state_initialisation, type_with_update_after_restore {

    /** Any course. */
    const WHICH_ANY = 0;
    /** One specific. */
    const WHICH_SPECIFIC = 1;

    public function initialise_state(objective_instance $instance, mission_instance $missioninst) {
        $objective = $instance->get_objective();
        $config = $objective->get_type_config();
        $which = $config->which;
        $missionid = $config->missionid ?? 0;

        if ($which != static::WHICH_SPECIFIC) {
            return false;
        }

        $mr = di::get('repository');
        $mh = di::get('mission_helper');

        try {
            $mission = $mr->get_instance_by_subject_id($missionid, $missioninst->get_subject_id());
        } catch (\moodle_exception $e) {
            return false;
        }

        if ($mh->has_completed($mission)) {
            $instance->increment_counter(1);
        }
    }

    public function consume_action(action $action, objective_instance $instance, mission_instance $missioninst) {
        if (!$action instanceof quest_finished) {
            return false;
        }
        $instance->increment_counter(1);
    }

    public function get_config_form_extender(mission $mission, ?objective $objective): \block_gearup\local\form\extender {
        return new finish_quest_config_form($mission, di::get('repository'));
    }

    public function get_display_name(): \lang_string {
        return new \lang_string('typefinishquest', 'block_gearup');
    }

    public function get_short_description(): \lang_string {
        return new \lang_string('typefinishquestdesc', 'block_gearup');
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof quest_finished;
    }

    public function is_action_passing_constraints(action $action, objective_instance $instance,
            mission_instance $missioninst): bool {
        if (!$action instanceof quest_finished) {
            return false;
        }

        $objective = $instance->get_objective();
        $config = $objective->get_type_config();

        $which = $config->which;
        $missionid = $config->missionid ?? 0;

        if ($which == static::WHICH_SPECIFIC) {
            return $missionid == $action->get_mission_id();
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
        $missionid = $config->missionid ?? 0;
        if (empty($missionid)) {
            return;
        }

        $newmissionid = $restore->get_mapping_id('block_gearup_mission', $missionid);
        if (!$newmissionid) {
            $restore->get_logger()->process("Mission ID $missionid not found", backup::LOG_INFO);
            return;
        }
        // Commit the change.
        try {
            if ($config->missionid == $newmissionid) {
                return;
            }
            $config->missionid = $newmissionid;
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
class finish_quest_config_form implements extender {

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

        $els[] = $mform->addElement('select', 'cd_which', get_string('elligiblequest', 'block_gearup'), [
            finish_quest::WHICH_ANY => get_string('anyquest', 'block_gearup'),
            finish_quest::WHICH_SPECIFIC => get_string('specificquest', 'block_gearup'),
        ]);

        $options = array_reduce($this->repository->get_quests($this->context), function($carry, $quest) {
            if ($quest->get_id() == $this->mission->get_id()) {
                return $carry;
            }
            $carry[$quest->get_id()] = $quest->get_title();
            return $carry;
        }, ['' => get_string('choosedots', 'core')]);
        $els[] = $mform->addElement('select', 'cd_missionid', get_string('quest', 'block_gearup'), $options);

        $mform->hideIf('cd_missionid', 'cd_which', 'eq', finish_quest::WHICH_ANY);

        // Tweak the count needed field.
        $mform->hideIf('countneeded', 'cd_which', 'eq', finish_quest::WHICH_SPECIFIC);
        $el = $mform->addElement($mform->removeElement('countneeded', true));
        $el->_helpbutton = '';
        $els[] = $el;

        return $els;
    }

    public function get_data($data) {
        if ($data->cd_which == finish_quest::WHICH_SPECIFIC) {
            $data->cd_missionid = (int) $data->cd_missionid;
            $data->countneeded = 1;
        } else {
            unset($data->cd_missionid);
        }
        $data->cd_which = (int) $data->cd_which;
        $data->countneeded = (int) $data->countneeded;
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (!isset($data->cd_which)) {
            $errors['cd_which'] = get_string('invaliddata', 'core_error');
        }
        if (($data->cd_which ?? null) == finish_quest::WHICH_SPECIFIC && empty($data->cd_missionid)) {
            $errors['cd_missionid'] = get_string('err_required', 'core_form');
        }
        return $errors;
    }

}
