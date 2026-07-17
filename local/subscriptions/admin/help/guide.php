<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\guides\HelpGuideService;
use local_subscriptions\crm\help\guides\HelpGuideRenderer;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$guideid = required_param(
    'id',
    PARAM_ALPHANUMEXT
);

$url = new moodle_url(
    subscription_config::admin_help_guide_page(),
    ['id' => $guideid]
);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(
    get_string(
        'crm_help_guides_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_help_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-help-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

$state = (new HelpGuideService())->get_state(
    (int)$USER->id,
    $guideid
);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_help_page()
    ),
    '← ' . get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'class' => 'crm-help-back-link',
    ]
);

echo HelpGuideRenderer::render_guide(
    $state,
    $url->out_as_local_url(false)
);

echo $OUTPUT->footer();