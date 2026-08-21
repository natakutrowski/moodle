<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\assistant\ai\rendering\CrmAssistantConversationRenderer;
use local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria;
use local_subscriptions\crm\assistant\rendering\CrmAssistantRenderer;
use local_subscriptions\crm\assistant\services\CrmAssistantService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

$scope = optional_param(
    'scope',
    AssistantRecommendationCriteria::SCOPE_ACTIVE,
    PARAM_ALPHA
);

$status = optional_param(
    'status',
    '',
    PARAM_ALPHANUMEXT
);

$type = optional_param(
    'type',
    '',
    PARAM_ALPHANUMEXT
);

$priority = optional_param(
    'priority',
    '',
    PARAM_ALPHANUMEXT
);

$page = optional_param(
    'page',
    0,
    PARAM_INT
);

$perpage = optional_param(
    'perpage',
    20,
    PARAM_INT
);

$page = max(0, $page);

if (
    !in_array(
        $perpage,
        [10, 20, 50],
        true
    )
) {
    $perpage = 20;
}

$status =
    $status !== '' &&
    RecommendationStatus::is_valid(
        $status
    )
        ? $status
        : null;

$type =
    $type !== '' &&
    RecommendationType::is_valid(
        $type
    )
        ? $type
        : null;

$criteria =
    new AssistantRecommendationCriteria(
        scope: $scope,
        status: $status,
        type: $type,
        prioritylevel:
            $priority !== ''
                ? $priority
                : null,
        limit: $perpage,
        offset: $page * $perpage
    );

$workspace = (
    new CrmAssistantService()
)->workspace(
    $criteria
);

$pageparams = [
    'scope' =>
        $criteria->scope,
    'perpage' =>
        $perpage,
];

if ($criteria->status !== null) {
    $pageparams['status'] =
        $criteria->status;
}

if ($criteria->type !== null) {
    $pageparams['type'] =
        $criteria->type;
}

if (
    $criteria->prioritylevel !== null
) {
    $pageparams['priority'] =
        $criteria->prioritylevel;
}

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_assistant_page(),
    $pageparams
);

$pagetitle = get_string(
    'crm_assistant_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-assistant-page'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/crm_assistant_ai',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::ASSISTANT,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_assistant_description',
        'local_subscriptions'
    ),
    HelpContext::ASSISTANT
);

$canuseassistantai = has_capability(
    Capabilities::USE_CRM_ASSISTANT_AI,
    $context
);

if ($canuseassistantai) {
    echo html_writer::start_div(
        'crm-assistant-main-dashboard'
    );

    echo html_writer::div(
        CrmAssistantConversationRenderer::render(),
        'crm-assistant-main-ai'
    );

    echo html_writer::div(
        CrmAssistantRenderer::overview_panel(
            $workspace->overview
        ),
        'crm-assistant-main-summary'
    );

    echo html_writer::end_div();
}

$paginationurl = new moodle_url(
    subscription_config::
        admin_crm_assistant_page(),
    $pageparams
);

$pagination = $OUTPUT->paging_bar(
    $workspace->total,
    $page,
    $perpage,
    $paginationurl
);

echo CrmAssistantRenderer::workspace(
    $workspace,
    $pagination,
    $perpage
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();