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
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

$threadid = required_param(
    'threadid',
    PARAM_INT
);

$readrepository = new InboxReadRepository();

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

$defaultsubject =
    trim((string)$thread->subject) !== ''
        ? (
            str_starts_with(
                \core_text::strtolower(
                    trim((string)$thread->subject)
                ),
                're:'
            )
                ? (string)$thread->subject
                : 'Re: ' . (string)$thread->subject
        )
        : 'Re:';

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

$action = optional_param(
    'formaction',
    '',
    PARAM_ALPHANUMEXT
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

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
            (int)$USER->id
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
            (int)$USER->id
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
}

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::
            admin_inbox_reply_page(),
        ['threadid' => $threadid]
    )
);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_inbox_reply',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_inbox_reply',
        'local_subscriptions'
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',

        'class' =>
            'card card-body crm-inbox-reply-form',

        'data-inbox-busy-form' => '1',

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

echo html_writer::label(
    get_string(
        'subject',
        'core'
    ),
    'id_subject',
    false,
    [
        'class' => 'form-label',
        'autocomplete' => 'off',
    ]
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

echo html_writer::label(
    get_string(
        'message',
        'core'
    ),
    'id_body',
    false,
    ['class' => 'form-label']
);

echo html_writer::tag(
    'textarea',
    s($body),
    [
        'name' => 'body',
        'id' => 'id_body',
        'rows' => 12,
        'class' => 'form-control mb-3',
        'required' => 'required',
        'aria-describedby' => 'crm-inbox-reply-help',
        'spellcheck' => 'true',
    ]
);

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
    get_string(
        'crm_inbox_save_draft',
        'local_subscriptions'
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
    get_string(
        'crm_inbox_send',
        'local_subscriptions'
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

echo $OUTPUT->footer();