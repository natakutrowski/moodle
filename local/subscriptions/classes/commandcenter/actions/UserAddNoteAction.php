<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

final class UserAddNoteAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::USER_ADD_NOTE;
    }

    public function capability(): string {
        return Capabilities::VIEW_USERS;
    }

    public function execute(array $payload): CommandActionResult {
        $userid = $this->int_payload($payload, 'userid');

        if ($userid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_user', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url(
            subscription_config::admin_user_add_note_page(),
            ['id' => $userid]
        ))->out(false));
    }
}