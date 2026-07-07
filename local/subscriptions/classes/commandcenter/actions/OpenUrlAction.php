<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use moodle_url;

final class OpenUrlAction extends AbstractCommandAction {

    public function key(): string {
        return 'open_url';
    }

    public function capability(): string {
        return Capabilities::VIEW_DASHBOARD;
    }

    public function execute(array $payload): CommandActionResult {
        $url = $this->string_payload($payload, 'url');

        if ($url === '') {
            return CommandActionResult::error(get_string('command_center_action_missing_url', 'local_subscriptions'));
        }

        if (strpos($url, '/') !== 0) {
            return CommandActionResult::error(get_string('command_center_action_invalid_url', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url($url))->out(false));
    }
}