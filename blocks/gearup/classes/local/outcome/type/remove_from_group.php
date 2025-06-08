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
use block_gearup\local\availability\course_context_required_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\availability\info_stack;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
use block_gearup\local\outcome\persisted_outcome;
use context;
use core_user;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class remove_from_group implements type, type_with_backup_handling, type_with_update_after_restore,
        has_availability_info_for_context {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        $user = core_user::get_user($userid);
        if (!$user) {
            return; // Odd...
        }

        if (!$config->groupid) {
            return; // Odd...
        }

        groups_remove_member($config->groupid, $missioninst->get_subject_id());
    }

    public function get_availability_info_for_context(context $context): info {
        global $USER;
        return new info_stack([
            new course_context_required_info($context),
            new permission_required_info('moodle/course:managegroups', $context, $USER->id),
        ]);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new remove_from_group_config_form_extender($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeremovefromgroup', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeremovefromgroupdesc', 'block_gearup');
    }

    public function extend_backup(backup_facade $backup, outcome $outcome, mission $mission) {
        $config = $outcome->get_type_config();
        $groupid = $config->groupid ?? 0;
        if ($groupid) {
            $backup->set_mapping_id('group', $groupid);
        }
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(),
                backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $groupid = $config->groupid ?? 0;

        try {
            $config->groupid = $restore->get_mapping_id('group', $groupid) ?? 0;
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
class remove_from_group_config_form_extender implements extender {

    protected $context;
    protected $mission;
    protected $repository;

    public function __construct(mission $mission) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
    }

    public function definition($mform): array {
        $groups = array_map(function($group) {
            return format_string($group->name, true, ['context' => $this->context]);
        }, groups_get_all_groups($this->context->instanceid));
        $els[] = $mform->addElement('select', 'cd_groupid', get_string('group', 'core'), $groups);
        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_groupid)) {
            $errors['cd_groupid'] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

}
