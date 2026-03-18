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
use block_gearup\local\availability\has_availability_info_for_user;
use block_gearup\local\availability\info;
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
use stdClass;

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
class email implements has_availability_info_for_user, type, type_with_backup_handling, type_with_update_after_restore {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        $user = core_user::get_user($userid);
        if (!$user) {
            return; // Odd...
        }

        $userfromid = $config->userfromid ?? 0;
        $subject = $config->subject ?? '';
        $content = $config->content ?? (object) ['text' => '', 'format' => FORMAT_PLAIN];
        $htmlcontent = format_text($content->text, $content->format, [
            'context' => $missioninst->get_mission()->get_context(),
            'filter' => false, // We do not want automatic linking, etc.
        ]);

        $this->send_email($user, $userfromid, $subject, $htmlcontent);
    }

    public function get_availability_info_for_user(int $userid, \context $context): info {
        return new permission_required_info('block/gearup:sendemail', $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new email_config_form_extender($mission->get_context());
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeemail', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeemaildesc', 'block_gearup');
    }

    /**
     * Send the email.
     *
     * @param stdClass $user The user to send to.
     * @param int $userfromid The user to send from.
     * @param string $subject The subject of the email.
     * @param string $content The HTML content of the email.
     */
    protected function send_email(stdClass $user, $userfromid, $subject, $content) {
        $userfrom = core_user::get_user($userfromid);
        if (!$userfrom) {
            $userfrom = core_user::get_noreply_user();
        }
        if (!$userfrom) {
            return;
        }

        $placeholders = [
            '[firstname]' => $user->firstname,
            '[fullname]' => fullname($user),
        ];
        $finalsubject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);

        $placeholders = [
            '[firstname]' => s($user->firstname),
            '[fullname]' => s(fullname($user)),
        ];
        $finalcontent = str_replace(array_keys($placeholders), array_values($placeholders), $content);

        email_to_user($user, $userfrom, $finalsubject, html_to_text($finalcontent), $finalcontent);
    }

    public function extend_backup(backup_facade $backup, outcome $outcome, mission $mission) {
        $config = $outcome->get_type_config();
        $userfromid = $config->userfromid;
        if ($userfromid > 0) {
            $backup->set_mapping_id('user', $userfromid);
        }
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $userfromid = $config->userfromid;
        if ($userfromid <= 0) {
            return; // Not a real user account.
        }

        $newuserfromid = $restore->get_mapping_id('user', $userfromid);
        if (!$newuserfromid) {
            $restore->get_logger()->process("User ID $userfromid not found", backup::LOG_INFO);
            return;
        } else if ($newuserfromid == $userfromid) {
            return;
        }

        try {
            $config->userfromid = $newuserfromid;
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
class email_config_form_extender implements extender {

    protected $context;

    public function __construct(context $context) {
        $this->context = $context;
    }

    public function definition($mform): array {
        global $USER;

        $currentuserfromid = $mform->_defaultValues['cd_userfromid'] ?? null;

        $els = [];

        $candidates = [];
        if ($currentuserfromid) {
            $candidates[] = core_user::get_user($currentuserfromid);
        }
        $candidates = array_merge($candidates, [$USER, core_user::get_noreply_user(), core_user::get_support_user()]);
        $options = [];
        foreach ($candidates as $sender) {
            if (array_key_exists($sender->id, $options)) {
                continue;
            } else if (!core_user::is_real_user($sender->id)) {
                continue;
            }
            $iscurrent = $sender->id == $currentuserfromid;
            $isme = $sender->id == $USER->id;
            $label = fullname($sender);
            if ($isme) {
                $label = get_string('valueyou', 'block_gearup', $label);
            } else if ($iscurrent) {
                $label = get_string('valuecurrent', 'block_gearup', $label);
            }
            $options[$sender->id] = $label;
        }
        $els[] = $mform->addElement('select', 'cd_userfromid', get_string('sender', 'block_gearup'), $options);

        $els[] = $mform->addElement('text', 'cd_subject', get_string('emailsubject', 'block_gearup'), ['size' => 60]);
        $mform->setType('cd_subject', PARAM_RAW);

        $els[] = $mform->addElement('editor', 'cd_content', get_string('emailtosend', 'block_gearup'));
        $mform->addRule('cd_content', get_string('err_required', 'core_form'), 'required', null, 'client');

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_userfromid)) {
            $errors['cd_userfromid'] = get_string('err_required', 'core_form');
        } else if (!has_capability('block/gearup:sendemail', $this->context, $data->cd_userfromid)) {
            // In order to always display a list of users to be selected from, we disregard whether they
            // have the capability to send emails, otherwise the list would be empty. However, in the
            // rare event where they cannot, then we display this error.
            $errors['cd_userfromid'] = get_string('usercannotsendemails', 'block_gearup');
        }

        return $errors;
    }

}
