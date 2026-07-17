<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandCenterRenderer;
use local_subscriptions\crm\assistant\dto\AssistantRecommendationCriteria;
use local_subscriptions\crm\assistant\rendering\CrmAssistantRenderer;
use local_subscriptions\crm\assistant\services\CrmAssistantService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\intelligence\recommendations\RecommendationStatus;
use local_subscriptions\crm\intelligence\recommendations\RecommendationType;
use local_subscriptions\crm\assistant\ai\rendering\CrmAssistantConversationRenderer;

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

$status = $status !== '' &&
    RecommendationStatus::is_valid($status)
        ? $status
        : null;

$type = $type !== '' &&
    RecommendationType::is_valid($type)
        ? $type
        : null;

$criteria = new AssistantRecommendationCriteria(
    scope: $scope,
    status: $status,
    type: $type,
    prioritylevel:
        $priority !== '' ? $priority : null,
    limit: 200
);

$workspace =
    (new CrmAssistantService())
        ->workspace($criteria);

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_assistant_page(),
    [
        'scope' => $criteria->scope,
        'status' => $criteria->status,
        'type' => $criteria->type,
        'priority' =>
            $criteria->prioritylevel,
    ]
);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title(
    get_string(
        'crm_assistant_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_assistant_title',
        'local_subscriptions'
    )
);
$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-assistant-page'
);
$PAGE->requires->css(
    new moodle_url(
        subscription_config::
            plugin_stylesheet_page()
    )
);
$PAGE->requires->js_call_amd(
    'local_subscriptions/command_center',
    'init'
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/crm_assistant_ai',
    'init'
);

echo $OUTPUT->header();

echo html_writer::start_div(
    'local-subscriptions-crm-workspace-shell'
);

echo CrmPageHeader::render(
    get_string(
        'crm_assistant_title',
        'local_subscriptions'
    ),
    get_string(
        'crm_assistant_description',
        'local_subscriptions'
    ),
    HelpContext::DASHBOARD
);

echo html_writer::start_div(
    'local-subscriptions-workspace-command'
);

echo CommandCenterRenderer::render();

echo html_writer::end_div();

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

echo html_writer::end_div();

echo $OUTPUT->footer();