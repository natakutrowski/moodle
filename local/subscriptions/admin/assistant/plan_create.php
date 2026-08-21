<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanManualCreationService;
use local_subscriptions\subscription_config;

global $DB, $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::MANAGE_USERS
);

$userid = required_param('userid', PARAM_INT);

$user = $DB->get_record(
    'user',
    [
        'id' => $userid,
        'deleted' => 0,
    ],
    'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename, email',
    MUST_EXIST
);

$pageurl = new moodle_url(
    subscription_config::
        admin_customer_success_plan_create_page(),
    ['userid' => $userid]
);

$pagetitle = get_string(
    'csplancreate_title_n129c',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-assistant-page',
        'local-subscriptions-customer-success-plan-create-page',
    ]
);

$parse_date = static function(
    string $value
): ?int {
    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        throw new moodle_exception(
            'invalidparameter'
        );
    }

    return $timestamp;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $priority = required_param(
        'priority',
        PARAM_ALPHANUMEXT
    );

    if (
        !in_array(
            $priority,
            [
                'low',
                'normal',
                'high',
                'urgent',
                'critical',
            ],
            true
        )
    ) {
        throw new moodle_exception(
            'invalidparameter'
        );
    }

    $planid =
        (new CustomerSuccessPlanManualCreationService())
            ->create(
                userid: $userid,
                title:
                    required_param(
                        'title',
                        PARAM_TEXT
                    ),
                description:
                    ($description = optional_param(
                        'description',
                        '',
                        PARAM_TEXT
                    )) !== ''
                        ? $description
                        : null,
                priority: $priority,
                actorid: (int)$USER->id,
                targetdate:
                    $parse_date(
                        optional_param(
                            'targetdate',
                            '',
                            PARAM_RAW_TRIMMED
                        )
                    ),
                firststeptitle:
                    ($firststeptitle = optional_param(
                        'firststeptitle',
                        '',
                        PARAM_TEXT
                    )) !== ''
                        ? $firststeptitle
                        : null,
                firststepdescription:
                    ($firststepdescription = optional_param(
                        'firststepdescription',
                        '',
                        PARAM_TEXT
                    )) !== ''
                        ? $firststepdescription
                        : null,
                firststepdueat:
                    $parse_date(
                        optional_param(
                            'firststepdueat',
                            '',
                            PARAM_RAW_TRIMMED
                        )
                    )
            );

    redirect(
        new moodle_url(
            subscription_config::
                admin_customer_success_plan_page(),
            ['id' => $planid]
        ),
        get_string(
            'csplancreate_success_n129c',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::ASSISTANT,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_users',
                    'local_subscriptions'
                ),
            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_users_page()
                ),
        ],
        [
            'label' =>
                fullname($user),
            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_user_view_page(),
                    ['id' => $userid]
                ),
        ],
        [
            'label' =>
                $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'csplancreate_help_n129c',
        'local_subscriptions',
        fullname($user)
    ),
    HelpContext::ASSISTANT
);

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' =>
            'crm-cs-plan-create-form',
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

echo html_writer::start_div(
    'crm-cs-plan-create-grid'
);

echo html_writer::start_div(
    'crm-cs-plan-create-main'
);

echo html_writer::tag(
    'h2',
    get_string(
        'csplancreate_plan_section_n129c',
        'local_subscriptions'
    ),
    ['class' => 'crm-cs-plan-create-section-title']
);

echo html_writer::label(
    get_string(
        'csplancreate_title_field_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_title',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'id' => 'id_cs_plan_title',
        'name' => 'title',
        'class' => 'form-control',
        'required' => 'required',
        'maxlength' => 255,
    ]
);

echo html_writer::label(
    get_string(
        'csplancreate_description_field_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_description',
    false,
    ['class' => 'form-label mt-3']
);

echo html_writer::tag(
    'textarea',
    '',
    [
        'id' => 'id_cs_plan_description',
        'name' => 'description',
        'class' => 'form-control',
        'rows' => 6,
    ]
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-cs-plan-create-sidebar'
);

echo html_writer::tag(
    'h2',
    get_string(
        'csplancreate_settings_section_n129c',
        'local_subscriptions'
    ),
    ['class' => 'crm-cs-plan-create-section-title']
);

$priorityoptions = [];

foreach (
    [
        'low',
        'normal',
        'high',
        'urgent',
        'critical',
    ]
    as $priority
) {
    $priorityoptions[$priority] =
        CustomerSuccessPlanPresentation::
            priority_label($priority);
}

echo html_writer::label(
    get_string(
        'csplancreate_priority_field_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_priority',
    false,
    ['class' => 'form-label']
);

echo html_writer::select(
    $priorityoptions,
    'priority',
    'normal',
    false,
    [
        'id' => 'id_cs_plan_priority',
        'class' => 'custom-select',
    ]
);

echo html_writer::label(
    get_string(
        'csplancreate_targetdate_field_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_targetdate',
    false,
    ['class' => 'form-label mt-3']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'datetime-local',
        'id' => 'id_cs_plan_targetdate',
        'name' => 'targetdate',
        'class' => 'form-control',
    ]
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-cs-plan-create-first-step'
);

echo html_writer::tag(
    'h2',
    get_string(
        'csplancreate_first_step_section_n129c',
        'local_subscriptions'
    ),
    ['class' => 'crm-cs-plan-create-section-title']
);

echo html_writer::div(
    get_string(
        'csplancreate_first_step_help_n129c',
        'local_subscriptions'
    ),
    'crm-cs-plan-create-section-help'
);

echo html_writer::label(
    get_string(
        'csplancreate_first_step_title_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_first_step_title',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'id' => 'id_cs_plan_first_step_title',
        'name' => 'firststeptitle',
        'class' => 'form-control',
        'maxlength' => 255,
    ]
);

echo html_writer::label(
    get_string(
        'csplancreate_first_step_description_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_first_step_description',
    false,
    ['class' => 'form-label mt-3']
);

echo html_writer::tag(
    'textarea',
    '',
    [
        'id' => 'id_cs_plan_first_step_description',
        'name' => 'firststepdescription',
        'class' => 'form-control',
        'rows' => 4,
    ]
);

echo html_writer::label(
    get_string(
        'csplancreate_first_step_due_n129c',
        'local_subscriptions'
    ),
    'id_cs_plan_first_step_due',
    false,
    ['class' => 'form-label mt-3']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'datetime-local',
        'id' => 'id_cs_plan_first_step_due',
        'name' => 'firststepdueat',
        'class' => 'form-control',
    ]
);

echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            subscription_config::
                admin_user_view_page(),
            ['id' => $userid]
        ),
        get_string(
            'cancel',
            'core'
        ),
        ['class' => 'btn btn-outline-secondary']
    )
    . html_writer::tag(
        'button',
        get_string(
            'csplancreate_submit_n129c',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'crm-cs-plan-create-actions'
);

echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
