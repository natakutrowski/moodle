<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;
use local_subscriptions\crm\inbox\ai\services\InboxAiDiagnosticsService;
use local_subscriptions\crm\inbox\ai\services\InboxAiQuotaService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
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

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_ai_diagnostics_page()
);

$pagetitle = get_string(
    'crm_inbox_ai_diagnostics',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-ai-diagnostics-page',
    ]
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::INBOX,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_inbox_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
        ],
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            admin_inbox_page()
    ),
    get_string(
        'crm_inbox_back',
        'local_subscriptions'
    )
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

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();