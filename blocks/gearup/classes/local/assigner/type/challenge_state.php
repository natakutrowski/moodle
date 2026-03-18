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
use block_gearup\local\action\challenge_completed;
use block_gearup\local\action\challenge_failed;
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
class challenge_state implements type, type_with_action_consumption, type_with_update_after_restore {

    /** Has finished. */
    const STATE_FINISHED = 1;
    /** Has succeeded. */
    const STATE_SUCCEEDED = 2;
    /** Has failed. */
    const STATE_FAILED = 3;

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new challenge_state_config_type_form($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignerchallengestate', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignerchallengestatedesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        $config = $assigner->get_type_config();
        $missionid = (int) ($config->missionid ?? null);
        $state = (int) ($config->state ?? null);

        $sql = 'SELECT 0 WHERE 1 = 0';
        $params = [];

        if ($state === static::STATE_SUCCEEDED) {
            $sql = "SELECT DISTINCT subjectid
                      FROM {block_gearup_mission_inst}
                     WHERE missionid = :id
                       AND state IN (:completed, :ended)
                       AND completionratio >= 1";
            $params = [
                'id' => $missionid,
                'completed' => mission_instance::STATE_COMPLETED,
                'ended' => mission_instance::STATE_ENDED,
            ];

        } else if ($state === static::STATE_FAILED) {
            $sql = "SELECT DISTINCT subjectid
                      FROM {block_gearup_mission_inst}
                     WHERE missionid = :id
                       AND state IN (:completed, :ended)
                       AND completionratio < 1";
            $params = [
                'id' => $missionid,
                'completed' => mission_instance::STATE_COMPLETED,
                'ended' => mission_instance::STATE_ENDED,
            ];

        } else if ($state === static::STATE_FINISHED) {
            $sql = "SELECT DISTINCT subjectid
                      FROM {block_gearup_mission_inst}
                     WHERE missionid = :id
                       AND state IN (:completed, :ended)";
            $params = [
                'id' => $missionid,
                'completed' => mission_instance::STATE_COMPLETED,
                'ended' => mission_instance::STATE_ENDED,
            ];
        }

        return [$sql, $params];
    }

    public function is_action_compatible(action $action): bool {
        return $action instanceof challenge_completed
            || $action instanceof challenge_failed;
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
        if ($state === static::STATE_SUCCEEDED) {
            return $action instanceof challenge_completed;

        } else if ($state === static::STATE_FAILED) {
            return $action instanceof challenge_failed;

        } else if ($state === static::STATE_FINISHED) {
            return $action instanceof challenge_completed
                || $action instanceof challenge_failed;
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
class challenge_state_config_type_form implements extender {

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

        $els[] = $mform->addElement('select', 'cd_state', get_string('challengerequiredstate', 'block_gearup'), [
            challenge_state::STATE_SUCCEEDED => get_string('challengestatesucceeded', 'block_gearup'),
            challenge_state::STATE_FAILED => get_string('challengestatefailed', 'block_gearup'),
            challenge_state::STATE_FINISHED => get_string('challengestatefinished', 'block_gearup'),
        ]);
        $mform->setDefault('cd_state', challenge_state::STATE_SUCCEEDED);
        $mform->addHelpButton('cd_state', 'challengerequiredstate', 'block_gearup');

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
        return get_string('challenge', 'block_gearup');
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_missionid)) {
            $errors['cd_missionid'] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

    protected function get_mission_options(): array {
        return array_reduce($this->repository->get_challenges($this->context), function ($carry, $quest) {
            if ($quest->get_id() == $this->mission->get_id()) {
                return $carry;
            }
            $carry[$quest->get_id()] = $quest->get_title();
            return $carry;
        }, []);
    }

}
