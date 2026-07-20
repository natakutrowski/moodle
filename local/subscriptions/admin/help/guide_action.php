<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\guides\HelpGuideService;

global $USER, $PAGE;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !==
    'POST'
) {
    throw new moodle_exception(
        'invalidrequest',
        'error'
    );
}

require_sesskey();

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

$guideid = required_param(
    'guide',
    PARAM_ALPHANUMEXT
);

$stepid = optional_param(
    'step',
    '',
    PARAM_ALPHANUMEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$fallbackurl = new moodle_url(
    subscription_config::admin_help_guide_page(),
    ['id' => $guideid]
);

$redirecturl = $returnurl !== ''
    ? new moodle_url($returnurl)
    : $fallbackurl;

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::admin_help_guide_action_page()
    )
);

$service = new HelpGuideService();

if ($action === 'toggle') {
    $service->toggle_step(
        (int)$USER->id,
        $guideid,
        $stepid
    );

    redirect($redirecturl);
}

if ($action === 'reset') {
    $service->reset(
        (int)$USER->id,
        $guideid
    );

    redirect(
        $redirecturl,
        get_string(
            'crm_help_guide_reset_success',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

throw new moodle_exception(
    'crm_help_guide_invalid_action',
    'local_subscriptions'
);