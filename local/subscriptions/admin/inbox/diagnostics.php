<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
use local_subscriptions\crm\inbox\services\InboxDiagnosticsService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$credentials =
    new MoodleConfigInboxCredentialStore();

$service = new InboxDiagnosticsService(
    new InboxAccountRepository(),
    new InboxDiagnosticsRepository(),
    $credentials,
    new OvhImapConnector(
        $credentials,
        new ImapMimeParser()
    ),
    new OvhSmtpConnector($credentials)
);

$result = $service->diagnose();

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::
            admin_inbox_diagnostics_page()
    )
);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(
    get_string(
        'crm_inbox_diagnostics',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_inbox_diagnostics',
        'local_subscriptions'
    )
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_inbox_page()
    ),
    '← ' . get_string(
        'crm_inbox_back',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-link ps-0 mb-3']
);

echo CrmPageHeader::render(
    get_string(
        'crm_inbox_diagnostics',
        'local_subscriptions'
    ),
    get_string(
        'crm_inbox_diagnostics_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_DIAGNOSTICS
);

echo html_writer::start_div(
    'crm-inbox-diagnostics'
);

foreach ($result['checks'] as $check) {
    echo html_writer::div(
        ($check['success'] ? '✓ ' : '✕ ') .
        s($check['message']),
        $check['success']
            ? 'alert alert-success'
            : 'alert alert-danger'
    );
}

if (!empty($result['metrics'])) {
    echo $OUTPUT->heading(
        get_string(
            'crm_inbox_diagnostics_metrics',
            'local_subscriptions'
        ),
        3
    );

    echo html_writer::start_tag(
        'table',
        ['class' => 'table table-striped']
    );

    foreach (
        $result['metrics']
        as $key => $value
    ) {
        echo html_writer::tag(
            'tr',
            html_writer::tag(
                'th',
                s($key)
            ) .
            html_writer::tag(
                'td',
                (string)$value
            )
        );
    }

    echo html_writer::end_tag('table');
}

echo html_writer::end_div();

echo $OUTPUT->footer();