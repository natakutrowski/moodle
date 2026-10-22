<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\accessscope\CommerceAccessScopePlanAttachmentService;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    throw new moodle_exception('invalidrequest');
}

$scopeid = required_param('scopeid', PARAM_INT);
$planid = required_param('planid', PARAM_INT);
$returnurl = new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $scopeid]);

$service = new CommerceAccessScopePlanAttachmentService($DB);
try {
    $service->attach($scopeid, $planid);
    redirect($returnurl, get_string('commerce_scope_plan_attached', 'local_subscriptions'));
} catch (\RuntimeException $exception) {
    redirect(
        $returnurl,
        get_string('commerce_scope_plan_attach_unsafe', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
