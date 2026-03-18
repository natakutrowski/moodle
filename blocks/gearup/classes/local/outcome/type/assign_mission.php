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

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assign_mission implements type, type_with_update_after_restore {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $mr = di::get('repository');
        $mo = di::get('mission_operator');

        $userid = $missioninst->get_subject_id();
        if (!core_user::is_real_user($userid, true)) {
            return; // Odd...
        }

        $mission = $mr->get_mission($this->get_mission_id($outcome));
        if (!$mission || $mission->get_state() !== mission::STATE_ACTIVE) {
            return;
        }

        $mo->assign_mission($mission, $userid);
    }

    protected function get_missionid_prop_in_config(): string {
        return 'missionid';
    }

    protected function get_mission_id(outcome $outcome): int {
        $name = $this->get_missionid_prop_in_config();
        return $outcome->get_type_config()->{$name} ?? 0;
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $configprop = $this->get_missionid_prop_in_config();
        $config = $outcome->get_type_config();
        $missionid = $config->{$configprop} ?? null;
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
            $config->{$configprop} = $newmissionid;
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
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class assign_mission_config_form_extender implements extender {

    protected $context;
    protected $mission;
    protected $repository;

    public function __construct(mission $mission, $repository) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
        $this->repository = $repository;
    }

    protected function get_field_name() {
        return 'cd_missionid';
    }

    abstract protected function get_label(): string;

    abstract protected function get_mission_options(): array;

    public function definition($mform): array {
        $options = ['' => get_string('choosedots', 'core')] + $this->get_mission_options();
        $els[] = $mform->addElement('select', $this->get_field_name(), $this->get_label(), $options);
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];
        $fieldname = $this->get_field_name();

        if (empty($data->{$fieldname})) {
            $errors[$fieldname] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

}
