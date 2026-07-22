<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\repositories\WorkTeamRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_WORK_CONFIGURATION);
$repository = new WorkTeamRepository();
$teams = $repository->all();
$users = $repository->eligible_users();
$pageurl = new moodle_url(
    subscription_config::
        admin_work_teams_page()
);

$pagetitle = get_string(
    'crm_work_teams',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-work-page',
        'local-subscriptions-work-teams-page',
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
    new moodle_url(
        subscription_config::
            admin_work_items_page()
    ),
    get_string(
        'crm_work_back',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_work_teams_subtitle',
        'local_subscriptions'
    ),
    HelpContext::WORK_ITEMS
);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => subscription_config::admin_work_team_action_page(),
    'class' => 'card card-body mb-4',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'create']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'name', 'class' => 'form-control mb-2', 'required' => 'required', 'placeholder' => get_string('crm_work_team_name', 'local_subscriptions')]);
echo html_writer::tag('textarea', '', ['name' => 'description', 'class' => 'form-control mb-2', 'rows' => 3]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('crm_work_team_create', 'local_subscriptions')]);
echo html_writer::end_tag('form');

foreach ($teams as $team) {
    $content = html_writer::tag('h3', format_string($team->name), ['class' => 'h5']);
    $content .= html_writer::div(format_text((string)$team->description, FORMAT_PLAIN), 'text-muted mb-3');
    foreach ($team->members as $member) {
        $content .= html_writer::start_tag('form', 
        [
            'method' => 'post', 
            'action' => subscription_config::admin_work_team_action_page(), 
            'class' => 'd-inline-block mr-2 mb-2',
            'data-confirm' => get_string(
                'crm_work_remove_member_confirm',
                'local_subscriptions'
            ),
        ]);
        $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'remove_member']);
        $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'teamid', 'value' => $team->id]);
        $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid', 'value' => $member->userid]);
        $content .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-danger', 'value' => fullname($member) . ' ×']);
        $content .= html_writer::end_tag('form');
    }

    $useroptions = [];
    foreach ($users as $user) { $useroptions[$user->id] = fullname($user); }
    $content .= html_writer::start_tag('form', ['method' => 'post', 'action' => subscription_config::admin_work_team_action_page(), 'class' => 'row g-2 mt-2']);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'add_member']);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'teamid', 'value' => $team->id]);
    $content .= html_writer::div(html_writer::select($useroptions, 'userid', '', false, ['class' => 'custom-select']), 'col-md-6');
    $content .= html_writer::div(html_writer::select(
        [
            'member' => get_string(
                'crm_work_team_role_member',
                'local_subscriptions'
            ),
            'lead' => get_string(
                'crm_work_team_role_lead',
                'local_subscriptions'
            ),
        ], 
        'role', 'member', false, ['class' => 'custom-select']), 'col-md-3');
    $content .= html_writer::div(html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('add')]), 'col-md-3');
    $content .= html_writer::end_tag('form');

    $content .= html_writer::start_tag('form', ['method' => 'post', 'action' => subscription_config::admin_work_team_action_page(), 'class' => 'mt-3']);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'toggle']);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'teamid', 'value' => $team->id]);
    $content .= html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-secondary', 'value' => get_string($team->enabled ? 'disable' : 'enable')]);
    $content .= html_writer::end_tag('form');

    echo html_writer::div($content, 'card card-body mb-3');
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();