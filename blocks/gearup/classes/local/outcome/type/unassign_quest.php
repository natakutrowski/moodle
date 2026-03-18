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
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use backup;
use block_gearup\di;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\persisted_outcome;
use core_user;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unassign_quest implements type, type_with_update_after_restore {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        $user = core_user::get_user($userid);
        if (!$user) {
            return; // Odd...
        }

        $questid = $config->questid ?? null;
        if (!$questid) {
            return;
        }

        $repository = di::get('repository');
        $operator = di::get('mission_operator');
        try {
            $instance = $repository->get_instance_by_subject_id($questid, $missioninst->get_subject_id());
            if (di::get('mission_helper')->is_active($instance)) {
                $operator->delete_instance($instance);
            }
        } catch (\moodle_exception $e) {
            return;
        }
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new unassign_quest_config_form_extender($mission, di::get('repository'));
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeunassignquest', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeunassignquestdesc', 'block_gearup');
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $questid = $config->questid ?? null;
        if (!$questid) {
            return;
        }

        $newquestid = $restore->get_mapping_id('block_gearup_mission', $questid);
        if (!$newquestid) {
            $restore->get_logger()->process("Quest ID $questid not found", backup::LOG_INFO);
            return;
        } else if ($newquestid == $questid) {
            return;
        }

        try {
            $config->questid = $newquestid;
            $outcome->get_persistent()->set('configdata', $config);
            $outcome->get_persistent()->update();
        } catch (\moodle_exception $e) {
            $restore->get_logger()->process("Error while updating outcome " . $outcome->get_id(), backup::LOG_WARNING);
        }
    }
}

/**
 * Form extender.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unassign_quest_config_form_extender implements extender {

    protected $context;
    protected $mission;
    protected $repository;

    public function __construct(mission $mission, $repository) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
        $this->repository = $repository;
    }

    public function definition($mform): array {

        $options = array_reduce($this->repository->get_quests($this->context), function ($carry, $quest) {
            if ($quest->get_id() == $this->mission->get_id()) {
                return $carry;
            }
            $carry[$quest->get_id()] = $quest->get_title();
            return $carry;
        }, ['' => get_string('choosedots', 'core')]);
        $els[] = $mform->addElement('select', 'cd_questid', get_string('quest', 'block_gearup'), $options);

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_questid)) {
            $errors['cd_questid'] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

}
