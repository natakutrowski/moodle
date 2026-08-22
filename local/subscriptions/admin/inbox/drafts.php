<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\repositories\InboxDraftRepository;
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

$repository = new InboxDraftRepository();

$discard = optional_param(
    'discard',
    0,
    PARAM_INT
);

if ($discard > 0) {
    require_sesskey();

    $repository->discard_compose_draft(
        $discard,
        (int)$USER->id
    );

    redirect(
        new moodle_url(
            subscription_config::
                admin_inbox_drafts_page()
        )
    );
}

$drafts = $repository->get_compose_drafts(
    (int)$USER->id
);

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_drafts_page()
);

$pagetitle = get_string(
    'crm_inbox_drafts_o7',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-drafts-page',
    ]
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
        'crm_inbox_drafts_subtitle_o7',
        'local_subscriptions'
    ),
    HelpContext::INBOX
);

echo InboxSectionNavigationRenderer::render(
    InboxSectionNavigationRenderer::DRAFTS
);


if ($drafts === []) {
    echo html_writer::div(
        get_string(
            'crm_inbox_no_drafts_o7',
            'local_subscriptions'
        ),
        'alert alert-light border'
    );
} else {
    echo html_writer::start_div(
        'crm-inbox-drafts-list'
    );

    foreach ($drafts as $draft) {
        $title = trim(
            (string)$draft->subject
        );

        if ($title === '') {
            $title = get_string(
                'crm_inbox_draft_without_subject_o7',
                'local_subscriptions'
            );
        }

        $preview = trim(
            (string)$draft->bodytext
        );

        if (\core_text::strlen($preview) > 160) {
            $preview = \core_text::substr(
                $preview,
                0,
                157
            ) . '…';
        }

        $resumeurl = new moodle_url(
            subscription_config::
                admin_inbox_compose_page(),
            ['threadid' => (int)$draft->threadid]
        );

        $discardurl = new moodle_url(
            subscription_config::
                admin_inbox_drafts_page(),
            [
                'discard' => (int)$draft->threadid,
                'sesskey' => sesskey(),
            ]
        );

        echo html_writer::start_div(
            'card card-body crm-inbox-draft-card '
            . 'crm-inbox-o15-draft-card'
        );

        echo html_writer::div(
            html_writer::tag(
                'strong',
                s($title)
            )
            . html_writer::span(
                userdate(
                    (int)$draft->timemodified,
                    get_string(
                        'strftimedatetimeshort',
                        'langconfig'
                    )
                ),
                'crm-inbox-draft-date'
            ),
            'crm-inbox-draft-heading'
        );

        echo html_writer::div(
            s($draft->accountemail),
            'crm-inbox-draft-account'
        );

        if ($preview !== '') {
            echo html_writer::div(
                s($preview),
                'crm-inbox-draft-preview'
            );
        }

        echo html_writer::div(
            html_writer::link(
                $resumeurl,
                html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true'])
                . get_string(
                    'crm_inbox_resume_draft_o7',
                    'local_subscriptions'
                ),
                ['class' => 'btn btn-primary btn-sm']
            )
            . html_writer::link(
                $discardurl,
                html_writer::tag('i', '', ['class' => 'fa fa-trash me-1', 'aria-hidden' => 'true'])
                . get_string(
                    'delete',
                    'core'
                ),
                ['class' => 'btn btn-sm btn-outline-primary crm-inbox-secondary-action']
            ),
            'crm-inbox-draft-actions'
        );

        echo html_writer::end_div();
    }

    echo html_writer::end_div();
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
