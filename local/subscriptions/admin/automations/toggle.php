<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\automation\AutomationRuleService;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_USERS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$service = new AutomationRuleService();

if ($action === 'enable') {
    $service->enable($id);
} else if ($action === 'disable') {
    $service->disable($id);
}

redirect(new moodle_url(subscription_config::automation_rules_admin_page()));