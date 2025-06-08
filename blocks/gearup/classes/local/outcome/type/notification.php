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

use block_gearup\local\availability\has_availability_info;
use block_gearup\local\availability\info;
use block_gearup\local\availability\static_info;
use block_gearup\local\form\extender;
use block_gearup\local\mission\mission;
use block_gearup\local\mission\mission_instance;
use block_gearup\local\outcome\outcome;
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
class notification implements type, has_availability_info {

    public function apply(outcome $outcome, mission_instance $missioninst) {
        $config = $outcome->get_type_config();
        $userid = $missioninst->get_subject_id();
        $this->send_message($userid, $config->subject, $config->message);
    }

    public function get_availability_info(): info {
        global $CFG;
        $reasons = [];

        $messagingenabled = !empty($CFG->messaging);
        if (!$messagingenabled) {
            $reasons[] = new lang_string('disabled', 'core_message');
        }

        return new static_info($messagingenabled, $reasons);
    }

    public function get_config_form_extender(mission $mission): \block_gearup\local\form\extender {
        return new notification_config_form_extender();
    }

    public function get_display_name(): lang_string {
        return new lang_string('outcomenotification', 'block_gearup');
    }

    public function get_short_description(): lang_string {
        return new lang_string('outcomenotificationdesc', 'block_gearup');
    }

    /**
     * Send the notification.
     *
     * This should not be used externally.
     *
     * @param int $userid The user to send to.
     * @param string $rawsubject The raw subject string.
     * @param string $rawmessage The raw message string.
     */
    public function send_message(int $userid, $rawsubject, $rawmessage) {
        $user = core_user::get_user($userid);
        $userfrom = core_user::get_noreply_user();

        if (!$user || !$userfrom) {
            return;
        }

        $placeholders = [
            '[firstname]' => $user->firstname,
            '[fullname]' => fullname($user),
        ];
        $messagetext = str_replace(array_keys($placeholders), array_values($placeholders), $rawmessage);
        $subjecttext = str_replace(array_keys($placeholders), array_values($placeholders), $rawsubject);

        $message = new \core\message\message();
        $message->component         = 'block_gearup';
        $message->name              = 'notificationoutcome';
        $message->notification      = 1;
        $message->userto            = $user;
        $message->userfrom          = $userfrom;
        $message->subject           = $subjecttext;
        $message->fullmessage       = $messagetext;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = '';

        // To be done when the quest gets its own URL.
        // $message->contextname       = null;
        // $message->contexturl        = null;
        message_send($message);
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
class notification_config_form_extender implements extender {

    public function definition($mform): array {
        $els[] = $mform->addElement('text', 'cd_subject', get_string('messagesubject', 'block_gearup'));
        $mform->addRule('cd_subject', get_string('err_required', 'core_form'), 'required', null, 'client');

        $els[] = $mform->addElement('textarea', 'cd_message', get_string('messagetosend', 'block_gearup'));
        $mform->addRule('cd_message', get_string('err_required', 'core_form'), 'required', null, 'client');

        return $els;
    }

    public function get_data($data) {
        return $data;
    }

    public function validation($data, $files) {
        $errors = [];

        return $errors;
    }

}
