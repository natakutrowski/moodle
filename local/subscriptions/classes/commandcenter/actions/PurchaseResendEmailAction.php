<?php

namespace local_subscriptions\commandcenter\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\DigitalPurchaseEmailService;

final class PurchaseResendEmailAction extends AbstractCommandAction {

    public function key(): string {
        return CommandActionKeys::PURCHASE_RESEND_EMAIL;
    }

    public function capability(): string {
        return Capabilities::MANAGE_DIGITAL;
    }

    public function execute(array $payload): CommandActionResult {
        $purchaseid = $this->int_payload($payload, 'purchaseid');

        if ($purchaseid <= 0) {
            return CommandActionResult::error(get_string('command_center_action_missing_purchase', 'local_subscriptions'));
        }

        DigitalPurchaseEmailService::resend_access_email($purchaseid);

        return CommandActionResult::success(
            get_string('command_center_purchase_email_resent', 'local_subscriptions'),
            null,
            [],
            true
        );
    }
}