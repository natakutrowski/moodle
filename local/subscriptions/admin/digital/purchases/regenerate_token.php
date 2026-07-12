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
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(
    subscription_config::digital_purchase_regenerate_token_admin_page(),
    [
        'id' => $id,
        'returnurl' => $returnurl,
    ]
));

DigitalPurchaseEmailService::regenerate_token($id);

redirect(
    $returnurl !== ''
        ? new moodle_url($returnurl)
        : new moodle_url(
            subscription_config::digital_purchase_view_admin_page(),
            ['id' => $id]
        ),
    get_string('digital_purchase_token_regenerated_success', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);