<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
use local_subscriptions\crm\inbox\repositories\InboxSyncLogRepository;
use local_subscriptions\crm\inbox\services\InboxDiagnosticsService;
use local_subscriptions\crm\inbox\rendering\InboxDiagnosticsRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$credentials =
    new MoodleConfigInboxCredentialStore();

$service = new InboxDiagnosticsService(
    new InboxAccountRepository(),
    new InboxDiagnosticsRepository(),
    $credentials,
    new OvhImapConnector(
        $credentials,
        new ImapMimeParser()
    ),
    new OvhSmtpConnector($credentials),
    new InboxSyncLogRepository()
);

$result = $service->diagnose();

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_diagnostics_page()
);

$pagetitle = get_string(
    'crm_inbox_diagnostics',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-diagnostics-page',
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

echo CrmPageHeader::render(
    get_string(
        'crm_inbox_diagnostics',
        'local_subscriptions'
    ),
    get_string(
        'crm_inbox_diagnostics_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_DIAGNOSTICS
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::DIAGNOSTICS
);


echo InboxDiagnosticsRenderer::render(
    $result,
    $pageurl,
    new moodle_url(
        subscription_config::admin_inbox_page()
    )
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();