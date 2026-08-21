<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\repositories\WorkTeamRepository;
use local_subscriptions\crm\work\rendering\WorkSectionNavigationRenderer;
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

echo WorkSectionNavigationRenderer::render(
    WorkSectionNavigationRenderer::TEAMS
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


echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_work_teams_subtitle',
        'local_subscriptions'
    ),
    HelpContext::WORK_ITEMS
);

echo html_writer::start_div(
    'crm-work-teams-grid'
);

echo html_writer::start_div(
    'crm-work-team-create crm-work-panel'
);

echo html_writer::tag(
    'h2',
    get_string(
        'crm_work_team_create',
        'local_subscriptions'
    ),
    ['class' => 'crm-work-panel-title']
);

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' =>
            subscription_config::
                admin_work_team_action_page(),
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'create',
    ]
);

echo html_writer::label(
    get_string(
        'crm_work_team_name',
        'local_subscriptions'
    ),
    'id_work_team_name',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'id' => 'id_work_team_name',
        'name' => 'name',
        'class' => 'form-control mb-3',
        'required' => 'required',
    ]
);

echo html_writer::label(
    get_string(
        'crm_work_team_description_n127a',
        'local_subscriptions'
    ),
    'id_work_team_description',
    false,
    ['class' => 'form-label']
);

echo html_writer::tag(
    'textarea',
    '',
    [
        'id' => 'id_work_team_description',
        'name' => 'description',
        'class' => 'form-control mb-3',
        'rows' => 5,
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string(
            'crm_work_team_create',
            'local_subscriptions'
        ),
    ]
);

echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div(
    'crm-work-team-list'
);

if (!$teams) {
    echo html_writer::div(
        get_string(
            'crm_work_team_empty_n127a',
            'local_subscriptions'
        ),
        'crm-work-panel text-muted'
    );
}

foreach ($teams as $team) {
    $members = '';

    foreach ($team->members as $member) {
        $members .= html_writer::start_tag(
            'form',
            [
                'method' => 'post',
                'action' =>
                    subscription_config::
                        admin_work_team_action_page(),
                'class' =>
                    'crm-work-team-member',
                'data-confirm' =>
                    get_string(
                        'crm_work_remove_member_confirm',
                        'local_subscriptions'
                    ),
            ]
        );

        $members .= html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'sesskey',
                'value' => sesskey(),
            ]
        );

        foreach (
            [
                'action' => 'remove_member',
                'teamid' => $team->id,
                'userid' => $member->userid,
            ]
            as $name => $value
        ) {
            $members .= html_writer::empty_tag(
                'input',
                [
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value,
                ]
            );
        }

        $members .= html_writer::span(
            fullname($member),
            'crm-work-team-member-name'
        );

        $members .= html_writer::span(
            s((string)$member->role),
            'crm-work-team-member-role'
        );

        $members .= html_writer::tag(
            'button',
            '×',
            [
                'type' => 'submit',
                'class' =>
                    'btn btn-link btn-sm text-danger p-0',
                'aria-label' =>
                    get_string(
                        'remove'
                    ),
            ]
        );

        $members .= html_writer::end_tag(
            'form'
        );
    }

    $useroptions = [];

    foreach ($users as $user) {
        $useroptions[$user->id] =
            fullname($user);
    }

    $add = html_writer::start_tag(
        'form',
        [
            'method' => 'post',
            'action' =>
                subscription_config::
                    admin_work_team_action_page(),
            'class' =>
                'crm-work-team-add-member',
        ]
    );

    $add .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]
    );

    $add .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'add_member',
        ]
    );

    $add .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'teamid',
            'value' => $team->id,
        ]
    );

    $add .= html_writer::select(
        $useroptions,
        'userid',
        '',
        false,
        ['class' => 'custom-select']
    );

    $add .= html_writer::select(
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
        'role',
        'member',
        false,
        ['class' => 'custom-select']
    );

    $add .= html_writer::empty_tag(
        'input',
        [
            'type' => 'submit',
            'class' =>
                'btn btn-outline-primary btn-sm',
            'value' => get_string('add'),
        ]
    );

    $add .= html_writer::end_tag('form');

    $toggle = html_writer::start_tag(
        'form',
        [
            'method' => 'post',
            'action' =>
                subscription_config::
                    admin_work_team_action_page(),
            'class' => 'crm-work-team-toggle',
        ]
    );

    $toggle .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]
    );

    $toggle .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'toggle',
        ]
    );

    $toggle .= html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'teamid',
            'value' => $team->id,
        ]
    );

    $toggle .= html_writer::empty_tag(
        'input',
        [
            'type' => 'submit',
            'class' =>
                'btn btn-outline-secondary btn-sm',
            'value' => get_string(
                $team->enabled
                    ? 'disable'
                    : 'enable'
            ),
        ]
    );

    $toggle .= html_writer::end_tag('form');

    echo html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'h2',
                    format_string($team->name),
                    ['class' => 'crm-work-team-title']
                )
                . html_writer::div(
                    format_text(
                        (string)$team->description,
                        FORMAT_PLAIN
                    ),
                    'crm-work-team-description'
                ),
                'crm-work-team-heading'
            )
            . html_writer::span(
                get_string(
                    $team->enabled
                        ? 'crm_work_team_enabled_n127a1'
                        : 'crm_work_team_disabled_n127a1',
                    'local_subscriptions'
                ),
                'badge '
                . (
                    $team->enabled
                        ? 'bg-success'
                        : 'bg-secondary'
                )
            ),
            'crm-work-team-header'
        )
        . html_writer::div(
            html_writer::tag(
                'h3',
                get_string(
                    'crm_work_team_members_n127a',
                    'local_subscriptions'
                ),
                ['class' => 'crm-work-team-subtitle']
            )
            . (
                $members !== ''
                    ? $members
                    : html_writer::div(
                        get_string(
                            'crm_work_team_no_members_n127a',
                            'local_subscriptions'
                        ),
                        'text-muted small'
                    )
            ),
            'crm-work-team-members'
        )
        . html_writer::div(
            $add,
            'crm-work-team-add'
        )
        . html_writer::div(
            $toggle,
            'crm-work-team-footer'
        ),
        'crm-work-team-card'
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();