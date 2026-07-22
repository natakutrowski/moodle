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
        limit: 200
    );

$workspace = (
    new CrmAssistantService()
)->workspace(
    $criteria
);

$pageparams = [
    'scope' =>
        $criteria->scope,
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

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_assistant_description',
        'local_subscriptions'
    ),
    HelpContext::ASSISTANT
);

if (
    has_capability(
        Capabilities::USE_CRM_ASSISTANT_AI,
        $context
    )
) {
    echo CrmAssistantConversationRenderer::render();
}

echo CrmAssistantRenderer::workspace(
    $workspace
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();