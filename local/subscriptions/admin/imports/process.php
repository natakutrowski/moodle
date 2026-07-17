<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib/lib_csv.php');
require_once(__DIR__ . '/../../renderer/user_subs_renderer.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::process_csv_page()));
$PAGE->set_title(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

echo $OUTPUT->header();
echo AdminNavigation::back_button();

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

require_sesskey();

$source = optional_param('sourcefile', '', PARAM_RAW);
if (!$source) {
    echo html_writer::div(get_string('missing_param', 'local_subscriptions'), 'subscription-message error');
    echo subscription_config::button_import_csv();
    echo $OUTPUT->footer();
    exit;
}

$validrows = unserialize(base64_decode($source, true)); 

if (!is_array($validrows) || empty($validrows)) {
    echo html_writer::div(get_string('no_valid_rows', 'local_subscriptions'), 'subscription-message error');
    echo $OUTPUT->footer();
    exit;
}

[$imported, $skipped] = process_csv_rows($validrows);

// Résumé
echo $renderer->render_import_summary($imported, $skipped);

// Lien vers page de gestion
echo subscription_config::button_manage_subscription();

echo $OUTPUT->footer();