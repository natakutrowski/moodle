<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

final class PurchaseRegenerateTokenAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::PURCHASE_REGENERATE_TOKEN;
    }

    public function capability(): string {
        return Capabilities::VIEW_DASHBOARD;
    }

    public function execute(array $payload): CommandActionResult {
        $purchaseid = $this->int_payload($payload, 'purchaseid');

        if ($purchaseid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_purchase', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url(
            subscription_config::digital_purchase_regenerate_token_admin_page(),
            ['id' => $purchaseid]
        ))->out(false));
    }
}