<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use moodle_url;

final class OpenProductAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::OPEN_PRODUCT;
    }

    public function capability(): string {
        return Capabilities::VIEW_DASHBOARD;
    }

    public function execute(array $payload): CommandActionResult {
        $productid = $this->int_payload($payload, 'productid');

        if ($productid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_product', 'local_subscriptions'));
        }

        return CommandActionResult::success('', (new moodle_url(
            subscription_config::digital_product_edit_admin_page(), ['id' => $productid]
        ))->out(false));
    }
}