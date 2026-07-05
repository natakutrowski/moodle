<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\dashboard\Dashboard;
use local_subscriptions\commandcenter\CommandCenterRenderer;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_DASHBOARD);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::admin_dashboard_page()));
$PAGE->set_title(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->set_heading(get_string('admin_dashboard', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));
$PAGE->requires->js_call_amd('local_subscriptions/command_center', 'init');

echo $OUTPUT->header();

echo CommandCenterRenderer::render();

echo Dashboard::render();

echo $OUTPUT->footer();