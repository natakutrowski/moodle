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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use backup;
use block_gearup\di;
use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
use block_gearup\local\availability\permission_required_info;
use block_gearup\local\availability\static_info;
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
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->dirroot . '/badges/lib/awardlib.php');

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_badge implements type_with_backup_handling, type_with_update_after_restore, user_facing_type,
        has_availability_info, has_availability_info_for_user {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        $user = core_user::get_user($userid);
        if (!$user) {
            return; // Odd...
        }

        // Identify the issuer, fallback on support user, or admin if needed.
        $issuerid = $config->issuerid;
        if (!core_user::is_real_user($userid, true)) {
            $issuer = core_user::get_support_user();
            if (!core_user::is_real_user($issuer->id)) {
                $issuer = get_admin();
            }
            $issuerid = $issuer->id;
        }

        $this->award_badge($userid, $config->badgeid, $issuerid);
    }

    public function get_availability_info(): info {
        global $CFG;
        $isenabled = !empty($CFG->enablebadges);
        return new static_info($isenabled, $isenabled ? [] : [
            new lang_string('badgesdisabled', 'core_badges'),
        ]);
    }

    public function get_availability_info_for_user(int $userid, context $context): info {
        return new permission_required_info(['moodle/badges:viewbadges', 'moodle/badges:awardbadge'], $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new award_badge_config_form_extender($mission);
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeawardbadge', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeawardbadgedesc', 'block_gearup');
    }


    /**
     * Award a badge.
     *
     * @param int $userid The user to award it to.
     * @param int $badgeid The badge ID.
     * @param int $issuerid The user issuing the badge.
     * @return void
     */
    public function award_badge(int $userid, int $badgeid, int $issuerid) { // @codingStandardsIgnoreLine
        try {
            $badge = new \core_badges\badge($badgeid);
        } catch (moodle_exception $e) {
            // The badge probably no longer exists.
            return;
        }

        // The user must be allowed to receive a badge.
        if (!has_capability('moodle/badges:earnbadge', $badge->get_context(), $userid)) {
            return;
        }

        if (!$badge->is_active()) {
            return;
        } else if (!$badge->has_manual_award_criteria()) {
            return;
        }

        $crit = $badge->criteria[BADGE_CRITERIA_TYPE_MANUAL];
        $roleids = array_keys($crit->params);
        if (empty($roleids)) {
            return;
        }

        // When only one role is required, we just keep one.
        if ($crit->method != BADGE_CRITERIA_AGGREGATION_ALL) {
            $roleids = [$roleids[0]];
        }

        // Award the badge by each role.
        foreach ($roleids as $roleid) {
            process_manual_award($userid, $issuerid, $roleid, $badgeid);
        }

        // Finally, trigger an update.
        $data = new \stdClass();
        $data->crit = $crit;
        $data->userid = $userid;
        badges_award_handle_manual_criteria_review($data);
    }

    public function extend_backup(backup_facade $backup, outcome $outcome, mission $mission) {
        $config = $outcome->get_type_config();
        $backup->set_mapping_id('badge', $config->badgeid);
        $backup->set_mapping_id('user', $config->issuerid);
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        global $USER;

        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $issuerid = $config->issuerid;
        $badgeid = $config->badgeid;
        $haschanged = false;

        $newissuerid = $restore->get_mapping_id('user', $issuerid);
        if (!$newissuerid) {
            $haschanged = true;
            $config->issuerid = $USER->id;
            $restore->get_logger()->process("User ID $issuerid not found", backup::LOG_INFO);
        } else if ($newissuerid != $issuerid) {
            $haschanged = true;
            $config->issuerid = $newissuerid;
        }

        $newbadgeid = $restore->get_mapping_id('badge', $badgeid);
        if (!$newbadgeid) {
            $haschanged = true;
            $config->badgeid = 0;
            $restore->get_logger()->process("Badge ID $badgeid not found", backup::LOG_INFO);
        } else if ($newbadgeid != $badgeid) {
            $haschanged = true;
            $config->badgeid = $newbadgeid;
        }

        if (!$haschanged) {
            return;
        }

        try {
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
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_badge_config_form_extender implements extender {

    protected $context;
    protected $mission;

    public function __construct(mission $mission) {
        $this->mission = $mission;
        $this->context = $mission->get_context();
    }

    public function definition($mform): array {
        $allbadges = $this->get_compatible_badges($this->context);
        $coursebadges = array_map(function($badge) {
            return $badge->name;
        }, array_filter($allbadges, function($badge) {
            return $badge->type == BADGE_TYPE_COURSE;
        }));
        $sitebadges = array_map(function($badge) {
            return $badge->name;
        }, array_filter($allbadges, function($badge) {
            return $badge->type == BADGE_TYPE_SITE;
        }));

        $options = [];
        if (!empty($coursebadges)) {
            $options[get_string('coursebadges', 'core_badges')] = $coursebadges;
        }
        if (!empty($sitebadges)) {
            $options[get_string('sitebadges', 'core_badges')] = $sitebadges;
        }
        if (empty($options)) {
            $options = [get_string('error', 'core') => [get_string('noresults', 'core')]];
        }

        $els[] = $mform->addElement('selectgroups', 'cd_badgeid', get_string('thebadge', 'block_gearup'), $options);

        $els[] = $mform->addElement('select', 'cd_issuerid', get_string('badgeissuer', 'block_gearup'),
            $this->get_possible_issuers($mform));

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_badgeid)) {
            $errors['cd_badgeid'] = get_string('err_required', 'core_form');
        }

        return $errors;
    }

    /**
     * Get the compatible badges.
     *
     * @param \context $context The context.
     * @return array
     */
    public function get_compatible_badges(\context $context) {
        global $DB;
        $sql = "
            SELECT b.id, b.name, b.type
              FROM {badge} b
              JOIN {badge_criteria} ba
                ON ba.badgeid = b.id
               AND ba.criteriatype = :criteriatype
             WHERE b.status IN (:active, :activelocked)
               AND ((b.type = :typecourse AND b.courseid = :courseid)
                OR b.type = :typesite)
          ORDER BY b.name ASC";
        $coursecontext = $context->get_course_context(false);
        $params = [
            'active' => BADGE_STATUS_ACTIVE,
            'activelocked' => BADGE_STATUS_ACTIVE_LOCKED,
            'criteriatype' => BADGE_CRITERIA_TYPE_MANUAL,
            'courseid' => $coursecontext ? $coursecontext->instanceid : 0,
            'typecourse' => BADGE_TYPE_COURSE,
            'typesite' => BADGE_TYPE_SITE,
        ];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get a list of possible issuers.
     *
     * @param object $mform The form.
     * @return array Indexed by user id, values are names.
     */
    public function get_possible_issuers($mform) {
        global $USER;

        $currentissuerid = $mform->_defaultValues['cd_issuerid'] ?? null;
        $issuercandidates = [];
        if ($currentissuerid) {
            $issuercandidates[] = core_user::get_user($currentissuerid);
        }
        $issuercandidates = array_merge($issuercandidates, [$USER, core_user::get_noreply_user(), core_user::get_support_user()]);

        $options = [];
        foreach ($issuercandidates as $issuer) {
            if (array_key_exists($issuer->id, $options)) {
                continue;
            } else if (!core_user::is_real_user($issuer->id)) {
                continue;
            }
            $iscurrent = $issuer->id == $currentissuerid;
            $isme = $issuer->id == $USER->id;
            $label = fullname($issuer);
            if ($isme) {
                $label = get_string('valueyou', 'block_gearup', $label);
            } else if ($iscurrent) {
                $label = get_string('valuecurrent', 'block_gearup', $label);
            }
            $options[$issuer->id] = $label;
        }

        return $options;
    }

}
