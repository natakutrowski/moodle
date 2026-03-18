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

namespace block_gearup\local\assigner\type;

use backup;
use block_gearup\di;
use block_gearup\local\action\action;
use block_gearup\local\action\mission_instance_action;
use block_gearup\local\action\quest_completed;
use block_gearup\local\action\quest_finished;
use block_gearup\local\action\quest_started;
use block_gearup\local\assigner\assigner;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use context;
use html_writer;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Assigner.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quest_state implements type, type_with_action_consumption, type_with_update_after_restore {

    /** In started state, or following. */
    const STATE_HAS_STARTED = 1;
    /** In completed state, or following. */
    const STATE_HAS_COMPLETED = 2;
    /** In state ended. */
    const STATE_IS_ENDED = 3;

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new quest_state_config_type_form($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignerqueststate', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignerqueststatedesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        global $DB;

        $config = $assigner->get_type_config();
        $missionid = (int) ($config->missionid ?? null);
        $state = (int) ($config->state ?? null);

        $sql = 'SELECT 0 WHERE 1 = 0';
        $params = [];

        $inparams = [];
        $insql = null;

        if ($state === static::STATE_HAS_STARTED) {
            [$insql, $inparams] = $DB->get_in_or_equal([mission_instance::STATE_ASSIGNED], SQL_PARAMS_NAMED, 'param', false);

        } else if ($state === static::STATE_HAS_COMPLETED) {
            [$insql, $inparams] = $DB->get_in_or_equal([
                mission_instance::STATE_COMPLETED,
                mission_instance::STATE_ENDED,
            ], SQL_PARAMS_NAMED);

        } else if ($state === static::STATE_IS_ENDED) {
            [$insql, $inparams] = $DB->get_in_or_equal([mission_instance::STATE_ENDED], SQL_PARAMS_NAMED);
        }

        if (!empty($insql)) {
            $sql = "SELECT DISTINCT subjectid
                      FROM {block_gearup_mission_inst}
                     WHERE missionid = :id
                       AND state $insql";
            $params = ['id' => $missionid] + $inparams;
        }

        return [$sql, $params];
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof quest_started
            || $action instanceof quest_completed
            || $action instanceof quest_finished;
    }

    public function is_action_passing_constraints(action $action, assigner $assigner): bool {
        if (!$action instanceof mission_instance_action) {
            return false;
        }

        $config = $assigner->get_type_config();
        $missionid = (int) ($config->missionid ?? null);
        if ($action->get_mission_id() !== $missionid) {
            return false;
        }

        $state = (int) ($config->state ?? null);
        if ($state === static::STATE_HAS_STARTED) {
            return $action instanceof quest_started;
        } else if ($state === static::STATE_HAS_COMPLETED) {
            return $action instanceof quest_completed;
        } else if ($state === static::STATE_IS_ENDED) {
            return $action instanceof quest_finished;
        }

        return false;
    }

    public function update_after_restore(restore_context $restore, assigner $assigner, mission $mission) {
        if (!$assigner instanceof persisted_assigner) {
            $restore->get_logger()->process("Cannot process after_restore of assigner " . $assigner->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $assigner->get_type_config();
        $missionid = $config->missionid ?? null;
        if (!$missionid) {
            return;
        }

        $newmissionid = $restore->get_mapping_id('block_gearup_mission', $missionid);
        if (!$newmissionid) {
            $restore->get_logger()->process("Mission ID $missionid not found", backup::LOG_INFO);
            return;
        } else if ($newmissionid == $missionid) {
            return;
        }

        try {
            $config->missionid = $newmissionid;
            $assigner->get_persistent()->set('configdata', $config);
            $assigner->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating assigner " . $assigner->get_id(), backup::LOG_WARNING);
        }
    }
}

/**
 * Form extender.
 *
 * @package    block_gearup
 * @copyright  2025 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quest_state_config_type_form implements extender {

    /** @var mission The context. */
    protected $mission;
    /** @var context The context. */
    protected $context;
    /** @var \block_gearup\local\repository The repository. */
    protected $repository;

    public function __construct(mission $mission) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
        $this->repository = di::get('repository');
    }

    public function definition($mform): array {
        // An archived or deleted mission will no longer appear in the list.
        $options = ['' => get_string('choosedots', 'core')] + $this->get_mission_options();
        $els[] = $mform->addElement('select', 'cd_missionid', $this->get_label(), $options);

        $els[] = $mform->addElement('select', 'cd_state', get_string('questrequiredstate', 'block_gearup'), [
            quest_state::STATE_HAS_STARTED => get_string('hasstarted', 'block_gearup'),
            quest_state::STATE_HAS_COMPLETED => get_string('hascompleted', 'block_gearup'),
            quest_state::STATE_IS_ENDED => get_string('isended', 'block_gearup'),
        ]);
        $mform->setDefault('cd_state', quest_state::STATE_IS_ENDED);
        $mform->addHelpButton('cd_state', 'questrequiredstate', 'block_gearup');

        // Workaround to leave room for the dropdown until MDL-70180 is integrated.
        $els[] = $mform->addElement('static', 'cd_spaced', '', html_writer::div('', 'gu-h-20'));

        return $els;
    }

    public function get_data($data) {
        $data->cd_missionid = (int) ($data->cd_missionid);
        $data->cd_state = (int) ($data->cd_state);
        return $data;
    }

    protected function get_label(): string {
        return get_string('quest', 'block_gearup');
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_missionid)) {
            $errors['cd_missionid'] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

    protected function get_mission_options(): array {
        return array_reduce($this->repository->get_quests($this->context), function ($carry, $quest) {
            if ($quest->get_id() == $this->mission->get_id()) {
                return $carry;
            }
            $carry[$quest->get_id()] = $quest->get_title();
            return $carry;
        }, []);
    }

}
