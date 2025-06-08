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
 * Cohort members assigner.
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
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\backup\restore_context;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use core\event\base as event;
use context;
use context_course;
use html_writer;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Cohort members assigner.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_members implements type, type_with_update_after_restore, type_with_event_consumption, has_availability_info_for_user {

    public function get_availability_info_for_user(int $userid, context $context): info {
        return new permission_required_info('moodle/cohort:view', $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        // TODO What happens when someone else edits this instance?
        // TODO Let users select the cohorts they can see (cohort_get_available_cohorts).
        return new cohort_members_config_type_form($mission->get_context());
    }

    public function get_display_name(): lang_string {
        return new lang_string('assignercohortmembers', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('assignercohortmembersdesc', 'block_gearup');
    }

    public function get_elligible_users_sql(assigner $assigner, mission $mission): array {
        global $DB;

        $config = $assigner->get_type_config();
        $cohortids = $config->cohortids ?? [];
        if (empty($cohortids)) {
            return ['SELECT 0 AS id', []];
        }

        list($insql, $inparams) = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED);
        $sql = "SELECT userid AS id FROM {cohort_members} WHERE cohortid $insql";
        $params = $inparams;
        return [$sql, $params];
    }

    public function get_elligile_users_sql_from_event(event $event, assigner $assigner, mission $mission): array {
        return ["SELECT {$event->relateduserid} AS id", []];
    }

    public function is_event_compatible(event $event): bool {
        return $event instanceof \core\event\cohort_member_added;
    }

    public function is_event_passing_constraints(event $event, assigner $assigner): bool {
        $config = $assigner->get_type_config();
        $cohortids = $config->cohortids ?? [];
        return in_array($event->objectid, $cohortids);
    }

    public function update_after_restore(restore_context $restore, assigner $assigner, mission $mission) {
        if (!$assigner instanceof persisted_assigner) {
            $restore->get_logger()->process("Cannot process after_restore of assigner " . $assigner->get_id(), backup::LOG_WARNING);
            return;
        }

        // No need to do anything on the same site.
        if ($restore->is_same_site()) {
            return;
        }
        // Remove the cohorts on other sites.
        try {
            $config = $assigner->get_type_config();
            $config->cohortids = [];
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
class cohort_members_config_type_form implements extender {

    /** @var context The context. */
    protected $context;

    public function __construct(context $context) {
        $this->context = $context;
    }

    public function definition($mform): array {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        // The function cohort_get_available_cohorts does not work with the system context. So the simplest workaround
        // is to give the context of the frontpage, so that it can resolve the system as its parent.
        $context = $this->context->contextlevel == CONTEXT_SYSTEM ? context_course::instance(SITEID) : $this->context;
        $options = array_map(function($cohort) {
            return $cohort->name;
        }, cohort_get_available_cohorts($context, COHORT_ALL, 0, 1000));

        $els = [];
        $els[] = $mform->addElement('autocomplete', 'cd_cohortids', get_string('cohorts', 'core_cohort'), $options, [
            'multiple' => true,
        ]);
        $mform->addRule('cd_cohortids', null, 'required', null, 'client');

        // Workaround to leave room for the dropdown until MDL-70180 is integrated.
        $els[] = $mform->addElement('static', 'cd_spaced', '', html_writer::div('', 'gu-h-20'));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        $cohortids = $data->cd_cohortids;
        if (empty($cohortids)) {
            $errors['cd_cohortids'] = get_string('invaliddata', 'core_error');
        }

        return $errors;
    }

}
