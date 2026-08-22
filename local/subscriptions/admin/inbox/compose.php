<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxContactRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;
use local_subscriptions\crm\inbox\services\InboxComposeService;
use local_subscriptions\crm\inbox\services\InboxRecipientService;
use local_subscriptions\crm\inbox\services\InboxReplyAttachmentService;
use local_subscriptions\crm\inbox\services\InboxTemplateService;
use local_subscriptions\crm\inbox\repositories\InboxTemplateRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;
use local_subscriptions\crm\inbox\rendering\InboxRecipientPickerRenderer;

$context = AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

$accountsrepo = new InboxAccountRepository();
$accounts = $accountsrepo->get_enabled();

if ($accounts === []) {
    throw new moodle_exception(
        'crm_inbox_account_not_found',
        'local_subscriptions'
    );
}

$defaultaccountid = (int)$accounts[0]->id;

$draftrepository = new InboxDraftRepository();
$readrepository = new InboxReadRepository();

$threadid = optional_param(
    'threadid',
    0,
    PARAM_INT
);

$draft = null;
$draftenvelope = [
    'to' => [],
    'cc' => [],
    'bcc' => [],
];

if ($threadid > 0) {
    $thread = $readrepository->get_thread(
        $threadid
    );

    if (!$thread) {
        throw new moodle_exception(
            'crm_inbox_draft_not_found_o7',
            'local_subscriptions'
        );
    }

    if ((string)$thread->folder !== 'DRAFTS') {
        $replydraft = $draftrepository->find_for_thread(
            $threadid
        );

        if ($replydraft) {
            redirect(
                new moodle_url(
                    subscription_config::admin_inbox_reply_page(),
                    [
                        'threadid' => $threadid,
                        'mode' => 'reply',
                    ]
                )
            );
        }

        redirect(
            new moodle_url(
                subscription_config::admin_inbox_thread_page(),
                ['id' => $threadid]
            )
        );
    }

    $draft = $draftrepository->find_for_thread(
        $threadid
    );

    if (!$draft) {
        throw new moodle_exception(
            'crm_inbox_draft_not_found_o7',
            'local_subscriptions'
        );
    }

    $draftenvelope = $draftrepository
        ->get_envelope(
            $draft
        );

    $defaultaccountid = (int)$thread->accountid;
}

$accountid = optional_param(
    'accountid',
    $defaultaccountid,
    PARAM_INT
);

$to = optional_param(
    'to',
    implode(', ', $draftenvelope['to']),
    PARAM_RAW_TRIMMED
);

$cc = optional_param(
    'cc',
    implode(', ', $draftenvelope['cc']),
    PARAM_RAW_TRIMMED
);

$bcc = optional_param(
    'bcc',
    implode(', ', $draftenvelope['bcc']),
    PARAM_RAW_TRIMMED
);

$subject = optional_param(
    'subject',
    $draft->subject ?? '',
    PARAM_TEXT
);

$body = optional_param(
    'body',
    $draft->bodytext ?? '',
    PARAM_RAW
);

$bodyhtml = optional_param(
    'bodyhtml',
    $draft->bodyhtml ?? '',
    PARAM_RAW
);

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    && !$draft
) {
    $bodyhtml = (
        new InboxTemplateService()
    )->append_signature(
        $accountid,
        $bodyhtml
    );
}


$action = optional_param(
    'formaction',
    'send',
    PARAM_ALPHA
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $recipientservice =
        new InboxRecipientService();

    $recipients = $recipientservice
        ->normalize(
            $to,
            $cc,
            $bcc
        );

    $uploads =
        InboxReplyAttachmentService::normalize_uploads(
            $_FILES['attachments'] ?? []
        );

    $inlineuploads =
        InboxReplyAttachmentService::normalize_uploads(
            $_FILES['inlineimages'] ?? []
        );

    $inlinecids =
        optional_param_array(
            'inlinecids',
            [],
            PARAM_RAW_TRIMMED
        );

    if ($action === 'save') {
        $autosave = new \local_subscriptions\crm\inbox\services\InboxDraftAutosaveService();

        $result = $autosave->save(
            'compose',
            $accountid,
            $threadid,
            $subject,
            $body,
            $bodyhtml,
            $recipients['to'],
            $recipients['cc'],
            $recipients['bcc'],
            (int)$USER->id
        );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_compose_page(),
                [
                    'threadid' =>
                        $result['threadid'],
                ]
            ),
            get_string(
                'crm_inbox_draft_saved',
                'local_subscriptions'
            )
        );
    }

    $service = new InboxComposeService(
        $accountsrepo,
        new InboxContactRepository(),
        new InboxReadRepository(),
        new InboxDraftRepository(),
        new InboxThreadRepository(),
        new OvhSmtpConnector(
            new MoodleConfigInboxCredentialStore()
        )
    );

    $threadid = $service->send(
        $accountid,
        $recipients['to'],
        $recipients['cc'],
        $recipients['bcc'],
        $subject,
        $body,
        (int)$USER->id,
        $uploads,
        $bodyhtml,
        $inlineuploads,
        $inlinecids,
        $threadid
    );

    redirect(
        new moodle_url(
            subscription_config::
                admin_inbox_thread_page(),
            ['id' => $threadid]
        ),
        get_string(
            'crm_inbox_message_sent_o6',
            'local_subscriptions'
        )
    );
}

$quickreplies = (
    new InboxTemplateService(
        new InboxTemplateRepository()
    )
)->quick_replies(
    $accountid
);

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_compose_page(),
    $threadid > 0
        ? ['threadid' => $threadid]
        : []
);

$pagetitle = get_string(
    'crm_inbox_new_message_o6',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-compose-page',
    ]
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
        'crm_inbox_new_message_subtitle_o6',
        'local_subscriptions'
    ),
    HelpContext::INBOX
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::COMPOSE
);


echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'class' =>
            'card card-body crm-inbox-reply-form '
            . 'crm-inbox-o15-composer-card',
        'data-inbox-busy-form' => '1',
        'data-inbox-autosave-form' => '1',
        'data-autosave-mode' => 'compose',
        'data-autosave-url' =>
            (new moodle_url(
                subscription_config::
                    admin_inbox_autosave_page()
            ))->out(false),
        'data-autosave-interval' => '8000',
        'data-autosave-saving' =>
            get_string(
                'crm_inbox_autosave_saving_o7',
                'local_subscriptions'
            ),
        'data-autosave-saved' =>
            get_string(
                'crm_inbox_autosave_saved_o7',
                'local_subscriptions'
            ),
        'data-autosave-error' =>
            get_string(
                'crm_inbox_autosave_error_o7',
                'local_subscriptions'
            ),
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
        'name' => 'threadid',
        'value' => $threadid,
        'data-inbox-draft-threadid' => '1',
    ]
);


$accountoptions = [];

foreach ($accounts as $account) {
    $accountoptions[(int)$account->id] =
        $account->name . ' · ' . $account->email;
}

echo html_writer::label(
    get_string(
        'crm_inbox_from_o6',
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
        'class' => 'form-select mb-3',
    ]
);

echo html_writer::start_div(
    'crm-inbox-compose-recipients mb-3'
);

echo InboxRecipientPickerRenderer::render(
    'to',
    get_string(
        'crm_inbox_to_o6',
        'local_subscriptions'
    ),
    $to,
    true
);

echo html_writer::div(
    html_writer::tag(
        'button',
        'Cc',
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-link px-0',
            'data-inbox-toggle-recipient' => 'cc',
        ]
    )
    . html_writer::span(' · ')
    . html_writer::tag(
        'button',
        'Cci',
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-link px-0',
            'data-inbox-toggle-recipient' => 'bcc',
        ]
    ),
    'crm-inbox-recipient-toggles'
);

echo html_writer::div(
    InboxRecipientPickerRenderer::render(
        'cc',
        'Cc',
        $cc
    ),
    'crm-inbox-recipient-optional'
    . ($cc === '' ? ' d-none' : ''),
    ['data-inbox-recipient-field' => 'cc']
);

echo html_writer::div(
    InboxRecipientPickerRenderer::render(
        'bcc',
        'Cci',
        $bcc
    ),
    'crm-inbox-recipient-optional'
    . ($bcc === '' ? ' d-none' : ''),
    ['data-inbox-recipient-field' => 'bcc']
);

echo html_writer::end_div();

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
        'class' => 'form-control mb-3',
        'required' => 'required',
    ]
);

if ($quickreplies !== []) {
    $quickoptions = [
        '' => get_string(
            'crm_inbox_quick_reply_choose_o9',
            'local_subscriptions'
        ),
    ];

    foreach ($quickreplies as $quickreply) {
        $quickoptions[(int)$quickreply->id] =
            (string)$quickreply->name;
    }

    echo html_writer::div(
        html_writer::select(
            $quickoptions,
            'quickreply',
            '',
            false,
            [
                'class' => 'form-select',
                'data-inbox-quick-reply-select' => '1',
                'data-template-url' => (
                    new moodle_url(
                        subscription_config::
                            admin_inbox_template_content_page()
                    )
                )->out(false),
            ]
        )
        . html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_inbox_templates_page()
            ),
            get_string(
                'crm_inbox_manage_templates_o9',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-outline-secondary',
            ]
        ),
        'crm-inbox-quick-reply-row mb-3'
    );
}

$editorhtml =
    $bodyhtml !== ''
        ? $bodyhtml
        : nl2br(
            s($body),
            false
        );

echo html_writer::start_div(
    'crm-inbox-rich-composer mb-3',
    [
        'data-inbox-rich-composer' => '1',
    ]
);

// O6.2: use Moodle's preferred HTML editor (TinyMCE on CampusFR) rather
// than maintaining a parallel contenteditable implementation. Inline CID
// images still use the Inbox attachment pipeline and are inserted by the
// Inbox AMD module into the editor instance.
echo html_writer::tag(
    'textarea',
    $editorhtml,
    [
        'name' => 'body_editor',
        'id' => 'id_compose_body_editor',
        'rows' => 14,
        'class' => 'form-control crm-inbox-rich-editor',
        'data-inbox-rich-editor' => '1',
    ]
);

$preferrededitor = editors_get_preferred_editor(FORMAT_HTML);
$preferrededitor->use_editor(
    'id_compose_body_editor',
    [
        'context' => $context,
        'maxfiles' => 0,
        'maxbytes' => 0,
        'noclean' => false,
        'subdirs' => 0,
    ]
);

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-image me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_inline_image_insert_o5',
                'local_subscriptions'
            )
        ),
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary',
            'data-inbox-inline-image-trigger' => '1',
        ]
    )
    . html_writer::empty_tag(
        'input',
        [
            'type' => 'file',
            'name' => 'inlineimages[]',
            'accept' => 'image/*',
            'multiple' => 'multiple',
            'class' => 'd-none',
            'data-inbox-inline-image-input' => '1',
            'data-max-file-size' =>
                InboxReplyAttachmentService::MAX_FILE_SIZE,
            'data-max-total-size' =>
                InboxReplyAttachmentService::MAX_TOTAL_SIZE,
        ]
    ),
    'crm-inbox-inline-image-actions mt-2'
);

echo html_writer::tag(
    'textarea',
    s($body),
    [
        'name' => 'body',
        'id' => 'id_compose_body',
        'class' => 'd-none',
        'data-inbox-body-text' => '1',
    ]
);

echo html_writer::tag(
    'textarea',
    s($bodyhtml),
    [
        'name' => 'bodyhtml',
        'id' => 'id_compose_bodyhtml',
        'class' => 'd-none',
        'data-inbox-body-html' => '1',
    ]
);

echo html_writer::div(
    '',
    'crm-inbox-inline-image-cids',
    [
        'data-inbox-inline-cid-container' => '1',
    ]
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-inbox-reply-attachments mb-3'
);

echo html_writer::label(
    get_string(
        'crm_inbox_attachments_o4',
        'local_subscriptions'
    ),
    'id_compose_attachments',
    false,
    ['class' => 'form-label fw-semibold']
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'file',
        'name' => 'attachments[]',
        'id' => 'id_compose_attachments',
        'class' =>
            'form-control crm-inbox-reply-file-input',
        'multiple' => 'multiple',
        'data-inbox-attachment-input' => '1',
        'data-max-files' =>
            InboxReplyAttachmentService::MAX_FILES,
        'data-max-file-size' =>
            InboxReplyAttachmentService::MAX_FILE_SIZE,
        'data-max-total-size' =>
            InboxReplyAttachmentService::MAX_TOTAL_SIZE,
        'data-existing-total-size' => 0,
    ]
);

echo html_writer::div(
    '',
    'crm-inbox-reply-new-files',
    [
        'data-inbox-attachment-list' => '1',
        'aria-live' => 'polite',
    ]
);

echo html_writer::div(
    '',
    'crm-inbox-reply-attachment-budget',
    [
        'data-inbox-attachment-budget' => '1',
        'aria-live' => 'polite',
    ]
);

echo html_writer::end_div();

echo html_writer::div(
    get_string(
        'crm_inbox_autosave_ready_o7',
        'local_subscriptions'
    ),
    'crm-inbox-autosave-status mb-2',
    [
        'data-inbox-autosave-status' => '1',
        'aria-live' => 'polite',
    ]
);

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-save me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_save_draft',
                'local_subscriptions'
            )
        ),
        [
            'type' => 'submit',
            'name' => 'formaction',
            'value' => 'save',
            'class' =>
                'btn btn-outline-secondary',
        ]
    )
    . html_writer::tag(
        'button',
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-paper-plane me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_send',
                'local_subscriptions'
            )
        ),

        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    )
    . html_writer::link(
        new moodle_url(
            subscription_config::
                admin_inbox_page()
        ),
        get_string('cancel', 'core'),
        ['class' => 'btn btn-link']
    ),
    'd-flex gap-2'
);

echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
