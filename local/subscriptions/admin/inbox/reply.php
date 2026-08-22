<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
use local_subscriptions\crm\inbox\repositories\InboxReadRepository;
use local_subscriptions\crm\inbox\repositories\InboxThreadRepository;
use local_subscriptions\crm\inbox\services\InboxReplyService;
use local_subscriptions\crm\inbox\services\InboxReplyAttachmentService;
use local_subscriptions\crm\inbox\services\InboxTemplateService;
use local_subscriptions\crm\inbox\repositories\InboxTemplateRepository;
use local_subscriptions\crm\inbox\repositories\InboxAttachmentRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\inbox\rendering\InboxSectionNavigationRenderer;
use local_subscriptions\crm\inbox\rendering\InboxRecipientPickerRenderer;

$context = AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

$threadid = required_param(
    'threadid',
    PARAM_INT
);

$readrepository = new InboxReadRepository();

$mode = optional_param(
    'mode',
    'reply',
    PARAM_ALPHA
);

if (!in_array(
    $mode,
    ['reply', 'replyall', 'forward'],
    true
)) {
    $mode = 'reply';
}

$thread = $readrepository->get_thread(
    $threadid
);

if (!$thread) {
    throw new moodle_exception(
        'crm_inbox_thread_not_found',
        'local_subscriptions'
    );
}

$draftrepository = new InboxDraftRepository();

$draft = $draftrepository
    ->find_for_thread($threadid);

$attachmentrepository =
    new InboxAttachmentRepository();

$draftattachments =
    $draft
        ? $attachmentrepository->get_for_message(
            (int)$draft->id
        )
        : [];

$draftenvelope = $draft
    ? $draftrepository->get_envelope(
        $draft
    )
    : [
        'to' => [],
        'cc' => [],
        'bcc' => [],
    ];

$draftattachmentbytes = array_sum(
    array_map(
        static fn(object $attachment): int =>
            max(0, (int)$attachment->filesize),
        $draftattachments
    )
);

$accountemail = \core_text::strtolower(
    trim((string)$thread->accountemail)
);

$defaultto = [];
$defaultcc = [];
$recipientlabels = [];

$lastinbound = $readrepository
    ->get_last_inbound_message(
        $threadid
    );

if ($lastinbound) {
    foreach (
        $readrepository->get_participants_for_message(
            (int)$lastinbound->id
        )
        as $participant
    ) {
        $email = \core_text::strtolower(
            trim((string)$participant->email)
        );

        if (
            $email === ''
            || $email === $accountemail
        ) {
            continue;
        }

        $displayname = trim(
            (string)($participant->displayname ?? '')
        );

        if ($displayname !== '') {
            $recipientlabels[$email] = $displayname;
        }

        if (
            $participant->participanttype === 'from'
        ) {
            $defaultto[$email] = $email;
        } elseif (
            $mode === 'replyall'
            && in_array(
                $participant->participanttype,
                ['to', 'cc'],
                true
            )
        ) {
            $defaultcc[$email] = $email;
        }
    }
}

if ($defaultto === []) {
    $contactemail = \core_text::strtolower(
        trim((string)$thread->contactemail)
    );

    if ($contactemail !== '') {
        $defaultto[$contactemail] =
            $contactemail;

        $contactname = trim(
            (string)($thread->contactname ?? '')
        );

        if ($contactname !== '') {
            $recipientlabels[$contactemail] =
                $contactname;
        }
    }
}

foreach (array_keys($defaultto) as $email) {
    unset($defaultcc[$email]);
}

if ($draft && $draftenvelope['to'] !== []) {
    $defaultto = array_combine(
        $draftenvelope['to'],
        $draftenvelope['to']
    ) ?: [];
    $defaultcc = array_combine(
        $draftenvelope['cc'],
        $draftenvelope['cc']
    ) ?: [];
}

if ($mode === 'forward' && !$draft) {
    $defaultto = [];
    $defaultcc = [];
}

$basesubject =
    trim((string)$thread->subject);

$defaultsubject =
    $mode === 'forward'
        ? (
            $basesubject !== ''
                ? (
                    str_starts_with(
                        \core_text::strtolower(
                            $basesubject
                        ),
                        'fwd:'
                    )
                        ? $basesubject
                        : 'Fwd: ' . $basesubject
                )
                : 'Fwd:'
        )
        : (
            $basesubject !== ''
                ? (
                    str_starts_with(
                        \core_text::strtolower(
                            $basesubject
                        ),
                        're:'
                    )
                        ? $basesubject
                        : 'Re: ' . $basesubject
                )
                : 'Re:'
        );

$to = optional_param(
    'to',
    implode(', ', array_values($defaultto)),
    PARAM_RAW_TRIMMED
);

$cc = optional_param(
    'cc',
    implode(', ', array_values($defaultcc)),
    PARAM_RAW_TRIMMED
);

$bcc = optional_param(
    'bcc',
    implode(
        ', ',
        $draftenvelope['bcc'] ?? []
    ),
    PARAM_RAW_TRIMMED
);

$subject = optional_param(
    'subject',
    $draft->subject ?? $defaultsubject,
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
        (int)$thread->accountid,
        $bodyhtml
    );
}


if (
    $mode === 'forward'
    && !$draft
    && $_SERVER['REQUEST_METHOD'] !== 'POST'
) {
    $messages = $readrepository
        ->get_messages($threadid);

    $lastmessage = $messages !== []
        ? end($messages)
        : null;

    if ($lastmessage) {
        $forwardtext = trim(
            (string)(
                $lastmessage->bodytext
                ?: strip_tags(
                    (string)$lastmessage->bodyhtml
                )
            )
        );

        $body =
            "\n\n---------- Message transféré ----------\n"
            . $forwardtext;

        $bodyhtml =
            '<p><br></p>'
            . '<hr>'
            . '<p><strong>'
            . s(
                get_string(
                    'crm_inbox_forwarded_message_o6',
                    'local_subscriptions'
                )
            )
            . '</strong></p>'
            . (
                !empty($lastmessage->bodyhtml)
                    ? (string)$lastmessage->bodyhtml
                    : nl2br(
                        s($forwardtext),
                        false
                    )
            );
    }
}

$action = optional_param(
    'formaction',
    '',
    PARAM_ALPHANUMEXT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $uploads =
        InboxReplyAttachmentService::normalize_uploads(
            $_FILES['attachments'] ?? []
        );

    $removeattachments =
        optional_param_array(
            'removeattachments',
            [],
            PARAM_INT
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

    $recipientservice =
        new \local_subscriptions\crm\inbox\services\InboxRecipientService();

    $recipientset =
        $recipientservice->normalize(
            $to,
            $cc,
            $bcc
        );

    $service = new InboxReplyService(
        new InboxAccountRepository(),
        $readrepository,
        $draftrepository,
        new InboxThreadRepository(),
        new OvhSmtpConnector(
            new MoodleConfigInboxCredentialStore()
        )
    );

    if ($action === 'save') {
        $service->save_draft(
            $threadid,
            $subject,
            $body,
            (int)$USER->id,
            $uploads,
            $removeattachments,
            $bodyhtml,
            $inlineuploads,
            $inlinecids,
            $recipientset['to'],
            $recipientset['cc'],
            $recipientset['bcc']
        );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_thread_page(),
                ['id' => $threadid]
            ),
            get_string(
                'crm_inbox_draft_saved',
                'local_subscriptions'
            )
        );
    }

    if ($action === 'send') {
        $service->send(
            $threadid,
            $subject,
            $body,
            (int)$USER->id,
            $uploads,
            $removeattachments,
            $bodyhtml,
            $inlineuploads,
            $inlinecids,
            $recipientset['to'],
            $recipientset['cc'],
            $recipientset['bcc']
        );

        redirect(
            new moodle_url(
                subscription_config::
                    admin_inbox_thread_page(),
                ['id' => $threadid]
            ),
            get_string(
                'crm_inbox_reply_sent',
                'local_subscriptions'
            )
        );
    }

    throw new moodle_exception(
        'crm_inbox_invalid_form_action',
        'local_subscriptions'
    );

}

$quickreplies = (
    new InboxTemplateService(
        new InboxTemplateRepository()
    )
)->quick_replies(
    (int)$thread->accountid
);

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_reply_page(),
    [
        'threadid' => $threadid,
        'mode' => $mode,
    ]
);

$pagetitle = get_string(
    'crm_inbox_reply',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-reply-page',
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

$threadurl = new moodle_url(
    subscription_config::
        admin_inbox_thread_page(),
    [
        'id' => $threadid,
    ]
);

$threadtitle =
    trim(
        (string)$thread->subject
    ) !== ''
        ? trim(
            (string)$thread->subject
        )
        : get_string(
            'crm_inbox_thread_without_subject',
            'local_subscriptions'
        );

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_inbox_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
                ),
        ],
        [
            'label' =>
                $threadtitle,

            'url' =>
                $threadurl,
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
        'crm_inbox_reply_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::INBOX
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
        'data-autosave-mode' => 'reply',
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

        'data-busy-announcement' =>
            get_string(
                'crm_inbox_reply_processing',
                'local_subscriptions'
            ),

        'aria-label' => get_string(
            'crm_inbox_reply_form_label',
            'local_subscriptions'
        ),
    ]
);

echo html_writer::div(
    '',
    'visually-hidden',
    [
        'role' => 'status',
        'aria-live' => 'polite',
        'aria-atomic' => 'true',
        'data-inbox-live-region' => '1',
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
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'accountid',
        'value' => (int)$thread->accountid,
    ]
);


echo html_writer::div(
    html_writer::link(
        $threadurl,
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-arrow-left me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_back_to_thread',
                'local_subscriptions'
            )
        ),
        [
            'class' =>
                'btn btn-sm btn-outline-secondary '
                . 'crm-inbox-o16-reply-back',
        ]
    ),
    'crm-inbox-o16-reply-form-toolbar'
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
    true,
    $recipientlabels
);

echo html_writer::div(
    html_writer::tag(
        'button',
        'Cc',
        [
            'type' => 'button',
            'class' =>
                'btn btn-sm btn-link px-0',
            'data-inbox-toggle-recipient' =>
                'cc',
        ]
    )
    . html_writer::span(' · ')
    . html_writer::tag(
        'button',
        'Cci',
        [
            'type' => 'button',
            'class' =>
                'btn btn-sm btn-link px-0',
            'data-inbox-toggle-recipient' =>
                'bcc',
        ]
    ),
    'crm-inbox-recipient-toggles'
);

echo html_writer::div(
    InboxRecipientPickerRenderer::render(
        'cc',
        'Cc',
        $cc,
        false,
        $recipientlabels
    ),
    'crm-inbox-recipient-optional'
    . ($cc === '' ? ' d-none' : ''),
    [
        'data-inbox-recipient-field' => 'cc',
    ]
);

echo html_writer::div(
    InboxRecipientPickerRenderer::render(
        'bcc',
        'Cci',
        $bcc
    ),
    'crm-inbox-recipient-optional'
    . ($bcc === '' ? ' d-none' : ''),
    [
        'data-inbox-recipient-field' => 'bcc',
    ]
);

echo html_writer::end_div();

echo html_writer::div(
    html_writer::label(
        get_string(
            'subject',
            'core'
        ),
        'id_subject',
        false,
        ['class' => 'form-label mb-0']
    )
    . html_writer::tag(
        'button',
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-pencil me-1',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            get_string(
                'crm_inbox_o16_3_edit_subject',
                'local_subscriptions'
            )
        ),
        [
            'type' => 'button',
            'class' =>
                'btn btn-sm btn-link '
                . 'crm-inbox-subject-edit',
            'data-inbox-subject-toggle' => '1',
        ]
    ),
    'crm-inbox-subject-heading'
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'name' => 'subject',
        'id' => 'id_subject',
        'value' => $subject,
        'class' =>
            'form-control mb-3 '
            . 'crm-inbox-subject-input',
        'required' => 'required',
        'readonly' => 'readonly',
        'data-inbox-subject-input' => '1',
    ]
);

echo html_writer::label(
    get_string(
        'message',
        'core'
    ),
    'id_body',
    false,
    ['class' => 'form-label']
);

$inlineimagemap = [];

foreach ($draftattachments as $attachment) {
    if (
        empty($attachment->isinline)
        || empty($attachment->contentid)
        || empty($attachment->fileitemid)
    ) {
        continue;
    }

    $inlineimagemap[] = [
        'cid' =>
            (string)$attachment->contentid,
        'url' =>
            subscription_config::
                inbox_attachment_url(
                    (int)$attachment->fileitemid,
                    (string)$attachment->filename
                )->out(false),
        'name' =>
            (string)$attachment->filename,
    ];
}

echo html_writer::div(
    '',
    'd-none',
    [
        'data-inbox-inline-existing' =>
            json_encode(
                $inlineimagemap,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE
            ),
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
        'id' => 'id_body_editor',
        'rows' => 14,
        'class' => 'form-control crm-inbox-rich-editor',
        'data-inbox-rich-editor' => '1',
        'aria-describedby' => 'crm-inbox-reply-help',
    ]
);

$preferrededitor = editors_get_preferred_editor(FORMAT_HTML);
$preferrededitor->use_editor(
    'id_body_editor',
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
        'id' => 'id_body',
        'class' => 'd-none',
        'data-inbox-body-text' => '1',
    ]
);

echo html_writer::tag(
    'textarea',
    s($bodyhtml),
    [
        'name' => 'bodyhtml',
        'id' => 'id_bodyhtml',
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

echo html_writer::div(
    get_string(
        'crm_inbox_inline_image_help_o5',
        'local_subscriptions'
    ),
    'form-text crm-inbox-inline-image-help'
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
    'id_attachments',
    false,
    [
        'class' =>
            'form-label fw-semibold',
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'file',
        'name' => 'attachments[]',
        'id' => 'id_attachments',
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
        'data-existing-total-size' =>
            $draftattachmentbytes,
        'aria-describedby' =>
            'crm-inbox-attachments-help',
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

echo html_writer::div(
    get_string(
        'crm_inbox_attachments_help_o4',
        'local_subscriptions',
        (object)[
            'count' =>
                InboxReplyAttachmentService::MAX_FILES,
            'each' => display_size(
                InboxReplyAttachmentService::
                    MAX_FILE_SIZE
            ),
            'total' => display_size(
                InboxReplyAttachmentService::
                    MAX_TOTAL_SIZE
            ),
        ]
    ),
    'form-text',
    [
        'id' =>
            'crm-inbox-attachments-help',
    ]
);

if ($draftattachments !== []) {
    echo html_writer::start_div(
        'crm-inbox-reply-existing-attachments'
    );

    echo html_writer::div(
        get_string(
            'crm_inbox_attachments_saved_o4',
            'local_subscriptions'
        ),
        'crm-inbox-reply-existing-title'
    );

    foreach ($draftattachments as $attachment) {
        if (!empty($attachment->isinline)) {
            continue;
        }

        $attachmentid =
            (int)$attachment->id;

        $label =
            (string)$attachment->filename
            . ' · '
            . display_size(
                (int)$attachment->filesize
            );

        echo html_writer::start_div(
            'crm-inbox-reply-existing-file'
        );

        echo html_writer::span(
            html_writer::tag(
                'i',
                '',
                [
                    'class' =>
                        'fa fa-paperclip',
                    'aria-hidden' =>
                        'true',
                ]
            )
            . s($label),
            'crm-inbox-reply-existing-file-name'
        );

        echo html_writer::label(
            html_writer::empty_tag(
                'input',
                [
                    'type' => 'checkbox',
                    'name' =>
                        'removeattachments[]',
                    'value' =>
                        $attachmentid,
                    'class' =>
                        'form-check-input me-1',
                ]
            )
            . get_string(
                'remove',
                'core'
            ),
            '',
            false,
            [
                'class' =>
                    'crm-inbox-reply-remove-file',
            ]
        );

        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

echo html_writer::end_div();

echo html_writer::div(
    get_string(
        'crm_inbox_reply_help',
        'local_subscriptions'
    ),
    'form-text mb-3',
    [
        'id' =>
            'crm-inbox-reply-help',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_autosave_ready_o7',
        'local_subscriptions'
    ),
    'crm-inbox-autosave-status',
    [
        'data-inbox-autosave-status' => '1',
        'aria-live' => 'polite',
    ]
);

echo html_writer::start_tag(
    'div',
    [
        'class' =>
            'd-flex flex-wrap gap-2 ' .
            'crm-inbox-reply-actions',

        'role' => 'group',

        'aria-label' => get_string(
            'crm_inbox_reply_actions_label',
            'local_subscriptions'
        ),
    ]
);

echo html_writer::tag(
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
        'data-loading-label' =>
            get_string(
                'crm_inbox_saving',
                'local_subscriptions'
            ),
    ]
);

echo html_writer::tag(
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
        'name' => 'formaction',
        'value' => 'send',
        'class' => 'btn btn-primary',
        'data-loading-label' =>
            get_string(
                'crm_inbox_sending',
                'local_subscriptions'
            ),
    ]
);

echo html_writer::link(
    new moodle_url(
        subscription_config::
            admin_inbox_thread_page(),
        [
            'id' => $threadid,
        ]
    ),
    get_string(
        'cancel',
        'core'
    ),
    [
        'class' =>
            'btn btn-link',
    ]
);

echo html_writer::end_tag(
    'div'
);

echo html_writer::end_tag('form');

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();