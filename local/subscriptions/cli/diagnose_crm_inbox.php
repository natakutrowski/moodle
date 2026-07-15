<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\repositories\InboxDiagnosticsRepository;
use local_subscriptions\crm\inbox\services\InboxDiagnosticsService;

$credentials =
    new MoodleConfigInboxCredentialStore();

$result = (
    new InboxDiagnosticsService(
        new InboxAccountRepository(),
        new InboxDiagnosticsRepository(),
        $credentials,
        new OvhImapConnector(
            $credentials,
            new ImapMimeParser()
        ),
        new OvhSmtpConnector($credentials)
    )
)->diagnose();

mtrace('CRM Inbox diagnostics');
mtrace('=====================');

foreach ($result['checks'] as $check) {
    mtrace(
        ($check['success'] ? '[OK] ' : '[ERROR] ') .
        $check['message']
    );
}

if (!empty($result['metrics'])) {
    mtrace('');
    mtrace('Metrics');

    foreach (
        $result['metrics']
        as $key => $value
    ) {
        mtrace('- ' . $key . ': ' . $value);
    }
}

exit($result['success'] ? 0 : 1);