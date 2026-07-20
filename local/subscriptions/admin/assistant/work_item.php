<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\intelligence\dto\CreateSuggestedWorkItemRequest;
use local_subscriptions\crm\work\intelligence\rendering\WorkItemSuggestionRenderer;
use local_subscriptions\crm\work\intelligence\services\SuggestedWorkItemCreationService;
use local_subscriptions\crm\work\intelligence\services\WorkItemSuggestionService;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::MANAGE_WORK_ITEMS
);

$recommendationid = required_param(
    'recommendationid',
    PARAM_INT
);

$suggestion =
    (new WorkItemSuggestionService())
        ->build($recommendationid);

$readrepository =
    new WorkItemReadRepository();

$teams = $readrepository->get_teams();
$assignees =
    $readrepository->get_assignees();

if (data_submitted()) {
    require_sesskey();

    $title = required_param(
        'title',
        PARAM_TEXT
    );

    $description = optional_param(
        'description',
        '',
        PARAM_TEXT
    );

    $type = required_param(
        'type',
        PARAM_ALPHANUMEXT
    );

    $priority = required_param(
        'priority',
        PARAM_ALPHANUMEXT
    );

    $assignedteamid = optional_param(
        'assignedteamid',
        0,
        PARAM_INT
    );

    $assigneduserid = optional_param(
        'assigneduserid',
        0,
        PARAM_INT
    );

    $dueatraw = optional_param(
        'dueat',
        '',
        PARAM_RAW_TRIMMED
    );

    $allowduplicate = optional_param(
        'allowduplicate',
        0,
        PARAM_BOOL
    );

    if (!WorkItemType::is_valid($type)) {
        throw new \invalid_parameter_exception(
            'Invalid Work Item type.'
        );
    }

    if (!WorkItemPriority::is_valid($priority)) {
        throw new \invalid_parameter_exception(
            'Invalid Work Item priority.'
        );
    }

    $dueat = $dueatraw !== ''
        ? strtotime($dueatraw)
        : null;

    try {
        $item =
            (new SuggestedWorkItemCreationService())
                ->create(
                    new CreateSuggestedWorkItemRequest(
                        recommendationid:
                            $recommendationid,
                        createdby:
                            (int)$USER->id,
                        title: $title,
                        description:
                            $description,
                        type: $type,
                        priority: $priority,
                        assignedteamid:
                            $assignedteamid > 0
                                ? $assignedteamid
                                : null,
                        assigneduserid:
                            $assigneduserid > 0
                                ? $assigneduserid
                                : null,
                        dueat:
                            $dueat ?: null,
                        allowduplicate:
                            (bool)$allowduplicate
                    )
                );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_work_item_view_page(),
                [
                    'id' => (int)$item->id,
                ]
            ),
            get_string(
                'crm_work_suggestion_created',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\DomainException $exception) {
        \core\notification::error(
            get_string(
                'crm_work_suggestion_duplicate_blocked',
                'local_subscriptions'
            )
        );
    }
}

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_assistant_work_item_page(),
    [
        'recommendationid' =>
            $recommendationid,
    ]
);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_work_suggestion_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_work_suggestion_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-assistant-page'
);
$PAGE->add_body_class(
    'local-subscriptions-assistant-work-item-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::
            plugin_stylesheet_page()
    )
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::ASSISTANT,
    $context
);

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_crm_assistant_page()
    ),
    '← ' . get_string(
        'crm_assistant_title',
        'local_subscriptions'
    ),
    [
        'class' =>
            'crm-app-back-link btn btn-link ps-0 mb-3',
    ]
);

echo WorkItemSuggestionRenderer::render(
    suggestion: $suggestion,
    teams: $teams,
    assignees: $assignees,
    actionurl: $pageurl
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();