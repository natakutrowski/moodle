<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxTemplateRepository;
use local_subscriptions\crm\inbox\services\InboxTemplateService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;

$context = AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

$repository = new InboxTemplateRepository();
$service = new InboxTemplateService(
    $repository
);

$editid = optional_param(
    'id',
    0,
    PARAM_INT
);

$deleteid = optional_param(
    'delete',
    0,
    PARAM_INT
);

if ($deleteid > 0) {
    require_sesskey();

    $repository->delete(
        $deleteid
    );

    redirect(
        new moodle_url(
            subscription_config::
                admin_inbox_templates_page()
        )
    );
}

$editing = $editid > 0
    ? $repository->find($editid)
    : null;

if ($editid > 0 && !$editing) {
    throw new moodle_exception(
        'crm_inbox_template_not_found_o9',
        'local_subscriptions'
    );
}

$type = optional_param(
    'type',
    $editing->type ?? InboxTemplateService::TYPE_QUICK_REPLY,
    PARAM_ALPHA
);

$name = optional_param(
    'name',
    $editing->name ?? '',
    PARAM_TEXT
);

$accountid = optional_param(
    'accountid',
    (int)($editing->accountid ?? 0),
    PARAM_INT
);

$subject = optional_param(
    'subject',
    $editing->subject ?? '',
    PARAM_TEXT
);

$bodyhtml = optional_param(
    'bodyhtml',
    $editing->bodyhtml ?? '',
    PARAM_RAW
);

$enabled = optional_param(
    'enabled',
    (int)($editing->enabled ?? 1),
    PARAM_BOOL
);

$sortorder = optional_param(
    'sortorder',
    (int)($editing->sortorder ?? 0),
    PARAM_INT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $saved = $service->save(
        $editid > 0 ? $editid : null,
        $accountid > 0
            ? $accountid
            : null,
        $type,
        $name,
        $subject,
        $bodyhtml,
        $enabled,
        $sortorder,
        (int)$USER->id
    );

    redirect(
        new moodle_url(
            subscription_config::
                admin_inbox_templates_page(),
            ['id' => (int)$saved->id]
        ),
        get_string(
            'crm_inbox_template_saved_o9',
            'local_subscriptions'
        )
    );
}

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_templates_page(),
    $editid > 0
        ? ['id' => $editid]
        : []
);

$pagetitle = get_string(
    'crm_inbox_templates_o9',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-templates-page',
    ]
);

$editor = editors_get_preferred_editor(
    FORMAT_HTML
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
            'label' => get_string(
                'crm_inbox_title',
                'local_subscriptions'
            ),
            'url' => new moodle_url(
                subscription_config::
                    admin_inbox_page()
            ),
        ],
        [
            'label' => $pagetitle,
            'url' => null,
        ],
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_inbox_templates_subtitle_o9',
        'local_subscriptions'
    ),
    HelpContext::INBOX
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::TEMPLATES
);


$records = $repository->get_all();

if ($records !== []) {
    echo html_writer::start_div(
        'crm-inbox-template-list mb-4'
    );

    foreach ($records as $record) {
        $editurl = new moodle_url(
            subscription_config::
                admin_inbox_templates_page(),
            ['id' => (int)$record->id]
        );

        $deleteurl = new moodle_url(
            subscription_config::
                admin_inbox_templates_page(),
            [
                'delete' => (int)$record->id,
                'sesskey' => sesskey(),
            ]
        );

        echo html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'strong',
                    s((string)$record->name)
                )
                . html_writer::span(
                    get_string(
                        $record->type === InboxTemplateService::TYPE_SIGNATURE
                            ? 'crm_inbox_template_type_signature_o9'
                            : 'crm_inbox_template_type_quickreply_o9',
                        'local_subscriptions'
                    ),
                    'badge bg-light text-dark border'
                ),
                'crm-inbox-template-title'
            )
            . html_writer::div(
                s(
                    trim(
                        (string)(
                            $record->bodytext
                            ?? ''
                        )
                    )
                ),
                'crm-inbox-template-preview'
            )
            . html_writer::div(
                html_writer::link(
                    $editurl,
                    html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true'])
                    . get_string(
                        'edit',
                        'core'
                    ),
                    [
                        'class' => 'btn btn-sm btn-primary',
                    ]
                )
                . html_writer::link(
                    $deleteurl,
                    html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true'])
                    . get_string(
                        'delete',
                        'core'
                    ),
                    [
                        'class' => 'btn btn-sm btn-outline-primary crm-inbox-secondary-action',
                    ]
                ),
                'crm-inbox-template-actions'
            ),
            'card card-body crm-inbox-template-card '
            . 'crm-inbox-o15-template-card'
        );
    }

    echo html_writer::end_div();
}

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'class' => 'card card-body',
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
    'crm-inbox-template-field'
);

echo html_writer::label(
    get_string(
        'crm_inbox_template_type_o9',
        'local_subscriptions'
    ),
    'id_type',
    false,
    ['class' => 'form-label']
);

echo html_writer::select(
    [
        InboxTemplateService::TYPE_QUICK_REPLY =>
            get_string(
                'crm_inbox_template_type_quickreply_o9',
                'local_subscriptions'
            ),
        InboxTemplateService::TYPE_SIGNATURE =>
            get_string(
                'crm_inbox_template_type_signature_o9',
                'local_subscriptions'
            ),
    ],
    'type',
    $type,
    false,
    [
        'id' => 'id_type',
        'class' => 'form-select',
        'data-inbox-template-type' => '1',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_type_help_o91',
        'local_subscriptions'
    ),
    'form-text'
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-inbox-template-field'
);

echo html_writer::label(
    get_string(
        'crm_inbox_template_name_o9',
        'local_subscriptions'
    ),
    'id_name',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'name' => 'name',
        'id' => 'id_name',
        'value' => $name,
        'class' => 'form-control',
        'required' => 'required',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_name_help_o91',
        'local_subscriptions'
    ),
    'form-text'
);

echo html_writer::end_div();

$accounts = (
    new InboxAccountRepository()
)->get_enabled();

$accountoptions = [
    0 => get_string(
        'crm_inbox_template_all_accounts_o9',
        'local_subscriptions'
    ),
];

foreach ($accounts as $account) {
    $accountoptions[(int)$account->id] =
        $account->name . ' · ' . $account->email;
}

echo html_writer::start_div(
    'crm-inbox-template-field'
);

echo html_writer::label(
    get_string(
        'crm_inbox_template_account_o9',
        'local_subscriptions'
    ),
    'id_accountid',
    false,
    ['class' => 'form-label']
);

echo html_writer::select(
    $accountoptions,
    'accountid',
    $accountid,
    false,
    [
        'id' => 'id_accountid',
        'class' => 'form-select',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_account_help_o91',
        'local_subscriptions'
    ),
    'form-text'
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-inbox-template-field'
    . (
        $type === InboxTemplateService::TYPE_SIGNATURE
            ? ' d-none'
            : ''
    ),
    [
        'data-inbox-template-subject-field' => '1',
    ]
);

echo html_writer::label(
    get_string('subject', 'core'),
    'id_subject',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'name' => 'subject',
        'id' => 'id_subject',
        'value' => $subject,
        'class' => 'form-control',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_subject_help_o91',
        'local_subscriptions'
    ),
    'form-text'
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-inbox-template-field crm-inbox-template-content-field'
);

echo html_writer::label(
    get_string(
        'crm_inbox_template_content_o9',
        'local_subscriptions'
    ),
    'id_bodyhtml',
    false,
    ['class' => 'form-label']
);

echo html_writer::tag(
    'textarea',
    s($bodyhtml),
    [
        'name' => 'bodyhtml',
        'id' => 'id_bodyhtml',
        'rows' => 12,
        'class' => 'form-control',
    ]
);

$editor->use_editor(
    'id_bodyhtml',
    [
        'autosave' => false,
        'maxfiles' => 0,
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_content_help_o91',
        'local_subscriptions'
    ),
    'form-text'
);

echo html_writer::end_div();

echo html_writer::div(
    html_writer::div(
        html_writer::empty_tag(
            'input',
            [
                'type' => 'checkbox',
                'name' => 'enabled',
                'id' => 'id_enabled',
                'value' => '1',
                ...($enabled
                    ? ['checked' => 'checked']
                    : []),
            ]
        )
        . html_writer::label(
            get_string(
                'crm_inbox_template_enabled_o9',
                'local_subscriptions'
            ),
            'id_enabled',
            false,
            ['class' => 'ms-2']
        ),
        'form-check'
    ),
    'my-3'
);

echo html_writer::label(
    get_string(
        'crm_inbox_template_sortorder_o9',
        'local_subscriptions'
    ),
    'id_sortorder',
    false,
    ['class' => 'form-label']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'number',
        'name' => 'sortorder',
        'id' => 'id_sortorder',
        'value' => $sortorder,
        'class' => 'form-control',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_template_sortorder_help_o91',
        'local_subscriptions'
    ),
    'form-text mb-3'
);

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', ['class' => 'fa fa-save me-1', 'aria-hidden' => 'true'])
        . get_string(
            'savechanges',
            'core'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'd-flex justify-content-end'
);

echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
