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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_gearup\local\outcome\type;

use backup;
use block_gearup\local\availability\admin_setting_info;
use block_gearup\local\availability\has_availability_info;
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
use context_system;
use core_date;
use core_user;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * Outcome.
 *
 * @package    block_gearup
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class private_message implements type, type_with_backup_handling, type_with_update_after_restore,
        has_availability_info, has_availability_info_for_user {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();

        $user = core_user::get_user($userid);
        if (!$user) {
            return; // Odd...
        }

        $this->send_message($userid, $config->userfromid, $config->message);
    }

    public function get_availability_info(): info {
        return new admin_setting_info('messaging', new lang_string('messaging', 'core_admin'));
    }

    public function get_availability_info_for_user(int $userid, \context $context): info {
        return new permission_required_info('moodle/site:sendmessage', $context, $userid);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new message_config_form_extender();
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomeprivatemessage', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomeprivatemessagedesc', 'block_gearup');
    }

    /**
     * Send the message.
     *
     * This should not be used externally.
     *
     * @param int $userid The user to send to.
     * @param int $userfromid The user to send from.
     * @param string $rawmessage The raw message string.
     */
    public function send_message(int $userid, int $userfromid, $rawmessage) {
        $user = core_user::get_user($userid);

        $userfrom = core_user::get_user($userfromid);
        if (!$userfrom) {
            $userfrom = core_user::get_noreply_user();
        }
        if (!$user || !$userfrom) {
            return;
        }

        $placeholders = [
            '[firstname]' => $user->firstname,
            '[fullname]' => fullname($user),
        ];
        $messagetext = str_replace(array_keys($placeholders), array_values($placeholders), $rawmessage);
        $message = new \core\message\message();
        $message->courseid          = SITEID;
        $message->component         = 'moodle';
        $message->name              = 'instantmessage';
        $message->notification      = 0;
        $message->userto            = $user;
        $message->userfrom          = $userfrom;
        $message->replyto           = $userfrom->email;
        $message->replytoname       = fullname($userfrom);
        $message->subject           = '';
        $message->fullmessage       = $messagetext;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = format_text($messagetext, FORMAT_PLAIN, ['filter' => false]);
        message_send($message);
    }

    public function extend_backup(backup_facade $backup, outcome $outcome, mission $mission) {
        $config = $outcome->get_type_config();
        $userfromid = $config->userfromid;
        $backup->set_mapping_id('user', $userfromid);
    }

    public function update_after_restore(restore_context $restore, outcome $outcome, mission $mission) {
        if (!$outcome instanceof persisted_outcome) {
            $restore->get_logger()->process("Cannot process after_restore of outcome " . $outcome->get_id(), backup::LOG_WARNING);
            return;
        }

        $config = $outcome->get_type_config();
        $userfromid = $config->userfromid;

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
 * @copyright  2021 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_config_form_extender implements extender {


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

        $els[] = $mform->addElement('textarea', 'cd_message', get_string('messagetosend', 'block_gearup'));
        $mform->addRule('cd_message', get_string('err_required', 'core_form'), 'required', null, 'client');

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        if (empty($data->cd_userfromid)) {
            $errors['cd_userfromid'] = get_string('err_required', 'core_form');
        } else if (!has_capability('moodle/site:sendmessage', context_system::instance(), $data->cd_userfromid)) {
            // In order to always display a list of users to be selected from, we disregard whether they
            // have the capability to send messages, otherwise the list would be empty. However, in the
            // rare event where they cannot, then we disable display this error. For now this is better
            // UX than displaying an empty list of options.
            $errors['cd_userfromid'] = get_string('usercannotsendmessages', 'block_gearup');
        }

        return $errors;
    }

}
