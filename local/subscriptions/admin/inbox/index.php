<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\dto\InboxThreadCriteria;
use local_subscriptions\crm\inbox\rendering\InboxRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

$criteria = InboxThreadCriteria::from_request();

$service = new InboxReadService(
    new InboxReadRepository(),
    new InboxTeamRepository()
);

$result = $service->search(
    $criteria,
    (int)$USER->id
);

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::admin_inbox_page(),
        $criteria->url_params()
    )
);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_inbox_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_inbox_title',
        'local_subscriptions'
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

$headeractions = '';

if (
    AdminSecurity::can(
        Capabilities::MANAGE_CONFIGURATION
    )
) {
    $headeractions = html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_diagnostics_page()
        ),
        get_string(
            'crm_inbox_diagnostics',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-sm btn-outline-secondary',
        ]
    );
}

echo CrmPageHeader::render(
    get_string(
        'crm_inbox_title',
        'local_subscriptions'
    ),
    get_string(
        'crm_inbox_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX,
    $headeractions
);

echo InboxRenderer::render($result);

echo $OUTPUT->footer();