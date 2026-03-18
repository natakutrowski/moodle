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
 * Group members assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\assigner\type;

use backup;
use block_gearup\local\assigner\assigner;
use block_gearup\local\assigner\persisted_assigner;
use block_gearup\local\availability\course_context_required_info;
use block_gearup\local\availability\has_availability_info_for_context;
use block_gearup\local\availability\info;
use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use context;
use context_course;
use html_writer;
use lang_string;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

/**
 * Group members assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_members implements
    has_availability_info_for_context,
    type,
    type_with_backup_handling,
    type_with_event_consumption,
    type_with_update_after_restore {

    public function get_availability_info_for_context(context $context): info {
        return new course_context_required_info($context, true);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        // TODO What happens when someone from another group edits this instance?
        return new group_members_config_type_form($mission->get_context());
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignergroupmembers', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignergroupmembersdesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        $config = $assigner->get_type_config();
        $context = $mission->get_context()->get_course_context();

        $groupids = $config->groupids ?? [];
        if (empty($groupids)) {
            return ['SELECT 0 AS id', []];
        }

        [$sql, $params] = groups_get_members_ids_sql($groupids, $context, GROUPS_JOIN_ANY);
        return [$sql, $params];
    }

    public function get_elligile_users_sql_from_event(\core\event\base $event, assigner $assigner, mission $mission): array {
        return ["SELECT {$event->relateduserid} AS id", []];
    }

    public function is_event_compatible(\core\event\base $event): bool {
        return $event instanceof \core\event\group_member_added;
    }

    public function is_event_passing_constraints(\core\event\base $event, assigner $assigner): bool {
        $config = $assigner->get_type_config();
        $groupids = $config->groupids ?? [];
        return in_array($event->objectid, $groupids);
    }

    public static function get_user_accessible_groups(context_course $context, $userid) {
        $course = get_fast_modinfo($context->instanceid, $userid)->get_course();
        $courseid = $course->id;
        $groupmode = $course->groupmode;

        $accessallgroups = has_capability('moodle/site:accessallgroups', $context, $userid);
        $accessallgroups = $accessallgroups || ($groupmode != SEPARATEGROUPS);
        if ($accessallgroups) {
            $groups = groups_get_all_groups($courseid);
        } else {
            $groups = groups_get_all_groups($courseid, $userid);
        }

        return $groups;
    }

    public function extend_backup(backup_facade $backup, assigner $assigner, mission $mission) {
        $config = $assigner->get_type_config();
        $groupids = $config->groupids ?? [];
        foreach ($groupids as $groupid) {
            $backup->set_mapping_id('group', $groupid);
        }
    }

    public function update_after_restore(restore_context $restore, assigner $assigner, mission $mission) {
        if (!$assigner instanceof persisted_assigner) {
            $restore->get_logger()->process("Cannot process after_restore of assigner " . $assigner->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $assigner->get_type_config();
        $groupids = $config->groupids ?? [];
        if (empty($groupids)) {
            return;
        }

        // Convert the role IDs.
        $newgroupids = array_values(array_filter(array_map(function ($groupid) use ($restore) {
            $newgroupid = $restore->get_mapping_id('group', $groupid);
            if (!$newgroupid) {
                $restore->get_logger()->process("Group ID $groupid not found", backup::LOG_INFO);
            }
            return $newgroupid;
        }, $groupids)));

        // Commit the change.
        try {
            if ($config->groupids === $newgroupids) {
                return;
            }
            $config->groupids = $newgroupids;
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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_members_config_type_form implements extender {

    /** @var context The context. */
    protected $context;

    public function __construct(context $context) {
        $this->context = $context;
    }

    public function definition($mform): array {
        global $USER;

        $groupoptions = array_map(function ($group) {
            return format_string($group->name, true, ['context' => $this->context]);
        }, group_members::get_user_accessible_groups($this->context->get_course_context(), $USER->id));

        $els = [];
        $els[] = $mform->addElement('autocomplete', 'cd_groupids', get_string('groups', 'core_group'), $groupoptions, [
            'multiple' => true,
        ]);
        $mform->addRule('cd_groupids', null, 'required', null, 'client');

        // Workaround to leave room for the dropdown until MDL-70180 is integrated.
        $els[] = $mform->addElement('static', 'cd_spaced', '', html_writer::div('', 'gu-h-20'));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        $groupids = $data->cd_groupids;
        if (empty($groupids)) {
            $errors['cd_groupids'] = get_string('invaliddata', 'core_error');
        }

        return $errors;
    }

}
