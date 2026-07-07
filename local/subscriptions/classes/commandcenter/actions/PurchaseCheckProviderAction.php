<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

final class PurchaseCheckProviderAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::PURCHASE_CHECK_PROVIDER;
    }

    public function capability(): string {
        return Capabilities::MANAGE_DIGITAL;
    }

    public function execute(array $payload): CommandActionResult {
        $purchaseid = $this->int_payload($payload, 'purchaseid');

        if ($purchaseid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_purchase', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url(
            subscription_config::digital_purchase_check_provider_admin_page(),
            ['id' => $purchaseid]
        ))->out(false));
    }
}