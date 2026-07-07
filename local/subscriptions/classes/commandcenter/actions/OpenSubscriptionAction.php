<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

final class OpenSubscriptionAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::OPEN_SUBSCRIPTION;
    }

    public function capability(): string {
        return Capabilities::VIEW_DASHBOARD;
    }

    public function execute(array $payload): CommandActionResult {
        $subscriptionid = $this->int_payload($payload, 'subscriptionid');

        if ($subscriptionid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_subscription', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url(
            subscription_config::user_subscription_view_page(), ['id' => $subscriptionid]
        ))->out(false));
    }
}