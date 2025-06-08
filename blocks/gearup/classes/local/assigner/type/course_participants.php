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
 * Course participants assigner.
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
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\backup\backup_facade;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use context;
use context_course;
use core\dml\sql_join;
use core_collator;
use html_writer;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Course participants assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_participants implements type, type_with_backup_handling, type_with_event_consumption, type_with_update_after_restore,
        has_availability_info_for_context, has_availability_info_for_user {

    public function get_availability_info_for_context(context $context): info {
        return new course_context_required_info($context);
    }

    public function get_availability_info_for_user(int $userid, context $context): info {
        // The participants page requires both permissions to view everyone in the course.
        return new permission_required_info([
            'moodle/course:viewparticipants',
            'moodle/site:accessallgroups',
        ], $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        // TODO What happens when someone else edits the instance and can't see the roles.
        return new course_participants_config_type_form($mission->get_context());
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignercourseparticipants', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignercourseparticipantsdesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        global $DB;

        $context = $mission->get_context()->get_course_context();
        $config = $assigner->get_type_config();

        $roleids = $config->roleids ?? [];
        $rolesjoin = new sql_join('', '1 = 1');
        if (!empty($roleids)) {
            list($inrolessql, $inrolesparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
            $rolessql = "SELECT userid
                           FROM {role_assignments}
                          WHERE roleid $inrolessql
                            AND contextid = :rolescontextid";
            $rolesparams = array_merge($inrolesparams, ['rolescontextid' => $context->id]);
            $rolesjoin = new sql_join("JOIN ($rolessql) r ON u.id = r.userid", '1 = 1', $rolesparams);
        }

        $enroljoin = get_enrolled_join($context, 'u.id');

        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                       {$enroljoin->joins}
                       {$rolesjoin->joins}
                 WHERE {$enroljoin->wheres}
                   AND {$rolesjoin->wheres}";
        $params = array_merge($enroljoin->params, $rolesjoin->params);

        return [$sql, $params];
    }

    public function get_elligile_users_sql_from_event(\core\event\base $event, assigner $assigner, mission $mission): array {

        // When the user was enrolled, we have already validated the constraints on role IDs.
        if ($event instanceof \core\event\user_enrolment_created) {
            return ["SELECT {$event->relateduserid} AS id", []];
        }

        // Here, the event would be the role assigned event, so we return a query that filters
        // the current user, but also includes the checks on whether they are enrolled or not
        // as we did not perform that check in the contraints checks.
        $context = $mission->get_context()->get_course_context();
        $enroljoin = get_enrolled_join($context, 'u.id');
        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                       {$enroljoin->joins}
                 WHERE {$enroljoin->wheres}
                   AND u.id = :userid";
        $params = array_merge($enroljoin->params, [
            'userid' => $event->relateduserid,
        ]);
        return [$sql, $params];
    }

    public function is_event_compatible(\core\event\base $event): bool {
        return $event instanceof \core\event\user_enrolment_created
            || $event instanceof \core\event\role_assigned;
    }

    public function is_event_passing_constraints(\core\event\base $event, assigner $assigner): bool {
        $config = $assigner->get_type_config();
        $roleids = $config->roleids ?? [];

        $isenrolcreated = $event instanceof \core\event\user_enrolment_created;
        $isroleassigned = $event instanceof \core\event\role_assigned;

        $context = $event->get_context();
        if (!$context) {
            // We are not guaranteed to get a context, but we need one!
            return false;
        } else if ($isroleassigned && !($context instanceof context_course)) {
            // Role assigned in another context than the course does not matter to us.
            return false;
        } else if ($isroleassigned && empty($roleids)) {
            // We never use the role assigned when the list of roles is empty.
            return false;
        }

        if ($isenrolcreated && empty($roleids)) {
            // Enrol created is triggered before the role is assigned, so we only use
            // it when we do not have any filters based on a role because when there
            // is a role, it is unlikely to be assigned yet, and we'll pick it with
            // the role assigned event.
            return true;

        } else if ($isroleassigned && in_array($event->objectid, $roleids)) {
            // Role assigned only matters when the role is in the list of roles. Note
            // that still does not guarantee that the user is enrolled.
            return true;
        }

        return false;
    }

    public function extend_backup(backup_facade $backup, assigner $assigner, mission $mission) {
        $config = $assigner->get_type_config();
        $roleids = $config->roleids ?? [];
        foreach ($roleids as $roleid) {
            $backup->set_mapping_id('role', $roleid);
        }
    }

    public function update_after_restore(restore_context $restore, assigner $assigner, mission $mission) {
        if (!$assigner instanceof persisted_assigner) {
            $restore->get_logger()->process("Cannot process after_restore of assigner " . $assigner->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $assigner->get_type_config();
        $roleids = $config->roleids ?? [];
        if (empty($roleids)) {
            return;
        }

        // Convert the role IDs.
        $newrolesids = array_values(array_filter(array_map(function($roleid) use ($restore) {
            $newroleid = $restore->get_mapping_id('role', $roleid);
            if (!$newroleid) {
                $restore->get_logger()->process("Role ID $roleid not found", backup::LOG_INFO);
            }
            return $newroleid;
        }, $roleids)));

        // Commit the change.
        try {
            if ($config->roleids === $newrolesids) {
                return;
            }
            $config->roleids = $newrolesids;
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
class course_participants_config_type_form implements extender {

    /** @var context The context. */
    protected $context;

    public function __construct(context $context) {
        $this->context = $context;
    }

    public function definition($mform): array {
        global $USER;

        $options = [];
        $options += get_viewable_roles($this->context, $USER->id, ROLENAME_BOTH);
        if (has_capability('moodle/role:assign', $this->context)) {
            $options += get_assignable_roles($this->context, ROLENAME_BOTH, false, $USER->id);
        }
        core_collator::asort($options, core_collator::SORT_NATURAL);

        $els = [];
        $els[] = $mform->addElement('autocomplete', 'cd_roleids', get_string('userswithrole', 'block_gearup'), $options, [
            'multiple' => true,
        ]);
        $mform->addRule('cd_roleids', null, 'required', null, 'client');
        $mform->addHelpButton('cd_roleids', 'userswithrole', 'block_gearup');

        // Set the default value to the student roles.
        $studentroles = get_archetype_roles('student');
        $defaultids = array_keys(array_intersect_key($options, $studentroles));
        $mform->setDefault('cd_roleids', $defaultids);

        // Workaround to leave room for the dropdown until MDL-70180 is integrated.
        $els[] = $mform->addElement('static', 'cd_spaced', '', html_writer::div('', 'gu-h-20'));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        $roleids = $data->cd_roleids;
        if (empty($roleids)) {
            $errors['cd_roleids'] = get_string('invaliddata', 'core_error');
        }

        return $errors;
    }

}
