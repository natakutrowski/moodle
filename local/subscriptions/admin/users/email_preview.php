<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\mail\MailRenderer;
use local_subscriptions\mailer;
use local_subscriptions\subscription_config;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

$logid = required_param(
    'logid',
    PARAM_INT
);

$log = $DB->get_record(
    'local_subscriptions_admin_log',
    [
        'id' => $logid,
    ],
    '*',
    MUST_EXIST
);

$details = json_decode(
    (string)$log->details,
    true
);

if (!is_array($details)) {
    $details = [];
}

$userid = (int)$log->targetuserid;

$backurl = new moodle_url(
    subscription_config::
        admin_user_view_page(),
    [
        'id' => $userid,
    ]
);

$user = $DB->get_record(
    'user',
    [
        'id' => $userid,
        'deleted' => 0,
    ],
    '*',
    MUST_EXIST
);

$subject = (string)(
    $details['subject'] ?? ''
);

$body = (string)(
    $details['body'] ?? ''
);

$buttonlabel = trim(
    (string)(
        $details['buttonlabel'] ?? ''
    )
);

$buttonurl = trim(
    (string)(
        $details['buttonurl'] ?? ''
    )
);

$buttonhtml = '';

if (
    $buttonlabel !== '' &&
    $buttonurl !== ''
) {
    $buttonhtml = mailer::email_button(
        $buttonurl,
        s($buttonlabel)
    );
}

$pageurl = new moodle_url(
    subscription_config::
        admin_user_email_preview_page(),
    [
        'logid' => $logid,
    ]
);

$pagetitle = get_string(
    'crm_email_preview',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-user-profile-page',
        'local-subscriptions-user-email-preview-page',
    ]
);

[$html, $text] = MailRenderer::layout(
    $subject,
    format_text(
        $body,
        FORMAT_HTML
    ) . $buttonhtml,
    '',
    ''
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::USERS,
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
                $backurl,
        ],
        [
            'label' =>
                get_string(
                    'crm_email_preview',
                    'local_subscriptions'
                ),

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    $backurl,
    get_string(
        'back',
        'core'
    )
);

echo CrmPageHeader::render(
    get_string(
        'crm_email_preview',
        'local_subscriptions'
    ),
    $user->email,
    HelpContext::EMAIL
);

echo html_writer::div(
    html_writer::tag(
        'strong',
        get_string(
            'subject',
            'core'
        ) .
        get_string(
            'labelsep',
            'langconfig'
        )
    ) .
    ' ' .
    s($subject),
    'crm-email-preview-subject'
);

echo html_writer::tag(
    'iframe',
    '',
    [
        'srcdoc' => $html,

        'title' => get_string(
            'crm_email_preview',
            'local_subscriptions'
        ),

        'class' =>
            'local-subscriptions-email-preview-frame',
    ]
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();