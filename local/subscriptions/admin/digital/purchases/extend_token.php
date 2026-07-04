<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\DigitalPurchaseEmailService;

global $PAGE;

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);
require_sesskey();

$id = required_param('id', PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::digital_purchase_extend_token_admin_page(), ['id' => $id]));

DigitalPurchaseEmailService::extend_token($id, 30);

redirect(
    new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $id]),
    get_string('digital_purchase_token_extended_success', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);