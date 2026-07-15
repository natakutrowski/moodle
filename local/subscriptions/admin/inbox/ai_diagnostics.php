<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;
use local_subscriptions\crm\inbox\ai\services\InboxAiDiagnosticsService;
use local_subscriptions\crm\inbox\ai\services\InboxAiQuotaService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$usage = new InboxAiUsageRepository();

$result = (
    new InboxAiDiagnosticsService(
        $usage,
        new InboxAiQuotaService($usage)
    )
)->diagnose(
    (int)$USER->id
);

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::
            admin_inbox_ai_diagnostics_page()
    )
);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_inbox_ai_diagnostics',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_inbox_ai_diagnostics',
        'local_subscriptions'
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(
        subscription_config::
            admin_inbox_page()
    ),
    '← ' .
    get_string(
        'crm_inbox_back',
        'local_subscriptions'
    ),
    [
        'class' =>
            'btn btn-link ps-0 mb-3',
    ]
);

echo CrmPageHeader::render(
    get_string(
        'crm_inbox_ai_diagnostics',
        'local_subscriptions'
    ),
    get_string(
        'crm_inbox_ai_diagnostics_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_AI
);

foreach ($result['checks'] as $check) {
    echo html_writer::div(
        ($check['success'] ? '✓ ' : '✕ ') .
        s((string)$check['message']),
        $check['success']
            ? 'alert alert-success'
            : 'alert alert-danger'
    );
}

echo $OUTPUT->heading(
    get_string(
        'crm_inbox_ai_usage_today',
        'local_subscriptions'
    ),
    3
);

echo html_writer::tag(
    'ul',
    html_writer::tag(
        'li',
        get_string(
            'crm_inbox_ai_usage_global',
            'local_subscriptions',
            (object)[
                'used' =>
                    $result['usage']['global'],
                'limit' =>
                    $result['usage']['globallimit'],
            ]
        )
    ) .
    html_writer::tag(
        'li',
        get_string(
            'crm_inbox_ai_usage_user',
            'local_subscriptions',
            (object)[
                'used' =>
                    $result['usage']['user'] ?? 0,
                'limit' =>
                    $result['usage']['userlimit'],
            ]
        )
    ) .
    html_writer::tag(
        'li',
        get_string(
            'crm_inbox_ai_failures_today',
            'local_subscriptions',
            $result['failures']
        )
    )
);

echo $OUTPUT->footer();