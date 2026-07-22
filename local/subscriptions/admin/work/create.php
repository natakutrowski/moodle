<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\rendering\WorkItemRenderer;
use local_subscriptions\crm\work\repositories\WorkItemReadRepository;
use local_subscriptions\crm\work\services\WorkItemService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmReturnUrlResolver;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_WORK_ITEMS);
$targetuserid = optional_param('targetuserid', 0, PARAM_INT);
$parentid = optional_param('parentid', 0, PARAM_INT);
$source = optional_param('source', WorkItemSource::MANUAL, PARAM_ALPHANUMEXT);
$threadid = optional_param('threadid', 0, PARAM_INT);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

if (!WorkItemSource::is_valid($source)) {
    $source = WorkItemSource::MANUAL;
}

if (data_submitted() && confirm_sesskey()) {
    $title = required_param('title', PARAM_TEXT);
    $description = optional_param('description', '', PARAM_TEXT);
    $type = required_param('type', PARAM_ALPHANUMEXT);
    $priority = required_param('priority', PARAM_ALPHANUMEXT);
    $assigneduserid = optional_param('assigneduserid', 0, PARAM_INT);
    $assignedteamid = optional_param('assignedteamid', 0, PARAM_INT);
    $dueatraw = optional_param('dueat', '', PARAM_RAW_TRIMMED);
    $dueat = $dueatraw !== '' ? strtotime($dueatraw) : null;

    $item = (new WorkItemService())->create(new CreateWorkItemRequest(
        $title,
        $description,
        $type,
        $priority,
        $source,
        (int)$USER->id,
        $targetuserid > 0 ? $targetuserid : null,
        $assigneduserid > 0 ? $assigneduserid : null,
        $assignedteamid > 0 ? $assignedteamid : null,
        $parentid > 0 ? $parentid : null,
        $dueat ?: null
    ));

    if ($threadid > 0 && $source === WorkItemSource::INBOX) {
        (new WorkItemService())->link(
            (int)$item->id,
            'inbox_thread',
            $threadid,
            \local_subscriptions\crm\work\domain\WorkItemRelation::CREATED_FROM,
            (int)$USER->id
        );
    }    

    redirect(
        new moodle_url(subscription_config::admin_work_item_view_page(), ['id' => $item->id]),
        get_string('crm_work_created', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$repository = new WorkItemReadRepository();
$teams = $repository->get_teams();
$assignees = $repository->get_assignees();

$pageparams = [];

if ($targetuserid > 0) {
    $pageparams['targetuserid'] =
        $targetuserid;
}

if ($parentid > 0) {
    $pageparams['parentid'] =
        $parentid;
}

if (
    $source !==
    WorkItemSource::MANUAL
) {
    $pageparams['source'] =
        $source;
}

if ($threadid > 0) {
    $pageparams['threadid'] =
        $threadid;
}

if ($returnurl !== '') {
    $pageparams['returnurl'] =
        $returnurl;
}

$pageurl = new moodle_url(
    subscription_config::
        admin_work_item_create_page(),
    $pageparams
);

$fallbackurl = new moodle_url(
    subscription_config::
        admin_work_items_page()
);

$backurl =
    CrmReturnUrlResolver::resolve(
        $returnurl,
        $fallbackurl
    );

$pagetitle = get_string(
    'crm_work_create',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-work-page',
        'local-subscriptions-work-create-page',
    ]
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::WORK,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_work_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_work_items_page()
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
    $backurl,
    get_string(
        'crm_work_back',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_work_create_subtitle',
        'local_subscriptions'
    ),
    HelpContext::WORK_ITEMS
);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'card card-body crm-work-create-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'targetuserid', 'value' => $targetuserid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'parentid', 'value' => $parentid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'source', 'value' => $source]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'threadid', 'value' => $threadid]);

if ($returnurl !== '') {
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'returnurl',
            'value' => $returnurl,
        ]
    );
}

echo html_writer::label(get_string('crm_work_field_title', 'local_subscriptions'), 'id_title');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'title', 'id' => 'id_title', 'class' => 'form-control mb-3', 'required' => 'required']);
echo html_writer::label(get_string('crm_work_field_description', 'local_subscriptions'), 'id_description');
echo html_writer::tag('textarea', '', ['name' => 'description', 'id' => 'id_description', 'rows' => 8, 'class' => 'form-control mb-3']);

echo html_writer::start_div('row g-3');
echo html_writer::div(html_writer::select(WorkItemRenderer::type_options(), 'type', WorkItemType::TASK, false, ['class' => 'custom-select']), 'col-md-3');
echo html_writer::div(html_writer::select(WorkItemRenderer::priority_options(), 'priority', WorkItemPriority::NORMAL, false, ['class' => 'custom-select']), 'col-md-3');
$teamoptions = [0 => get_string('none')];
foreach ($teams as $team) { $teamoptions[$team->id] = format_string($team->name); }
echo html_writer::div(html_writer::select($teamoptions, 'assignedteamid', 0, false, ['class' => 'custom-select']), 'col-md-3');
$useroptions = [0 => get_string('none')];
foreach ($assignees as $user) { $useroptions[$user->id] = fullname($user); }
echo html_writer::div(html_writer::select($useroptions, 'assigneduserid', 0, false, ['class' => 'custom-select']), 'col-md-3');
echo html_writer::end_div();

echo html_writer::label(get_string('crm_work_due', 'local_subscriptions'), 'id_dueat', false, ['class' => 'mt-3']);
echo html_writer::empty_tag('input', ['type' => 'datetime-local', 'name' => 'dueat', 'id' => 'id_dueat', 'class' => 'form-control mb-3']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('crm_work_create', 'local_subscriptions')]);
echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();