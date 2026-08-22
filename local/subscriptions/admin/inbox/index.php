<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\inbox\dto\InboxThreadCriteria;
use local_subscriptions\crm\inbox\workspace\InboxWorkspaceRenderer;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxTeamRepository;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxReadService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_INBOX
);

$criteria =
    InboxThreadCriteria::from_request();

$service = new InboxReadService(
    new InboxReadRepository(),
    new InboxTeamRepository(),
    new InboxAccountRepository()
);

$result = $service->search(
    $criteria,
    (int)$USER->id
);

$pageurl = new moodle_url(
    subscription_config::admin_inbox_page(),
    $criteria->url_params()
);

$pagetitle = get_string(
    'crm_inbox_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-inbox-page'
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

$headeractions = '';

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_inbox_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX,
    $headeractions
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::INBOX
);




echo InboxWorkspaceRenderer::render(
    $result,
    (int)$USER->id
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();