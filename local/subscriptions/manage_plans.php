<?php

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_config;

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.in.js'), true);
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/init_select2.js'), true);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));


$PAGE->set_url(new moodle_url(subscription_config::manage_plans_page()));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('managesubscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('managesubscriptions', 'local_subscriptions'));

echo $OUTPUT->header();

// Tabs
$currenttab = optional_param('tab', 'scopes', PARAM_ALPHA);

$tabs = [
    new tabobject('scopes', new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes']), get_string('scopes', 'local_subscriptions')),
    new tabobject('plans', new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'plans']), get_string('plans', 'local_subscriptions')),
];

print_tabs([$tabs], $currenttab);

// Include selected tab
switch ($currenttab) {
    case 'plans':
        include_once(__DIR__ . '/tabs/plans.php');
        break;
    case 'scopes':
    default:
        include_once(__DIR__ . '/tabs/scopes.php');
        break;
}

echo $OUTPUT->footer();
