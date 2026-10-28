<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

namespace mod_minilesson\task;

/**
 * Send an optional MiniLesson view log to CloudPoodll after the HTTP request completes.
 *
 * @package    mod_minilesson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cloudpoodll_usage_log extends \core\task\adhoc_task {
    /**
     * @return string
     */
    public function get_name() {
        return get_string('taskcloudpoodllusagelog', 'mod_minilesson');
    }

    /**
     * @return void
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data->cmid)) {
            return;
        }

        $language = isset($data->language) ? (string)$data->language : '';
        if (isset($data->owner, $data->ownercomphash)) {
            $identity = [
                'owner' => (string)$data->owner,
                'ownercomphash' => (string)$data->ownercomphash,
            ];
        } else {
            // Backward compatibility for tasks already queued by 1.1.60b.
            // Never send an event attributed to the cron/system user.
            global $DB;
            $userid = (int)$this->get_userid();
            $user = $userid ? $DB->get_record('user', ['id' => $userid], 'id,username') : false;
            if (!$user || empty($user->username)) {
                mtrace('[mod_minilesson] Skipping legacy usage log without originating user, cmid=' .
                    (int)$data->cmid);
                return;
            }
            $identity = [
                'owner' => md5($user->username),
                'ownercomphash' => md5($user->username . \mod_minilesson\constants::M_COMPONENT .
                    (int)$data->cmid . date('Y-m-d')),
            ];
        }
        $ok = \mod_minilesson\utils::stage_remote_process_job(
            $language, (int)$data->cmid, 5, $identity
        );
        if (!$ok) {
            // This call is optional telemetry. Do not retry indefinitely on remote failure.
            mtrace('[mod_minilesson] CloudPoodll usage log could not be sent for cmid=' . (int)$data->cmid);
        }
    }
}
