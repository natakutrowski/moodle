<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxFolderDiscoveryService;
use local_subscriptions\crm\inbox\services\InboxFolderResolver;

$accounts = new InboxAccountRepository();

$account = $accounts->find_by_email(
    'support@campusfr.fr'
);

if (!$account) {
    throw new moodle_exception(
        'crm_inbox_account_not_found',
        'local_subscriptions'
    );
}

$credentials =
    new MoodleConfigInboxCredentialStore();

$service = new InboxFolderDiscoveryService(
    new OvhImapConnector(
        $credentials,
        new ImapMimeParser()
    ),
    new InboxFolderResolver(),
    $accounts
);

$result = $service->discover(
    $account,
    true
);

mtrace('CRM Inbox folder discovery');
mtrace('==========================');

foreach ($result['folders'] as $folder) {
    mtrace(
        '- ' . $folder->name .
        (
            $folder->specialuse !== null
                ? ' [' . $folder->specialuse . ']'
                : ''
        )
    );
}

mtrace('');
mtrace('Resolved folders:');

foreach ($result['resolved'] as $type => $folder) {
    mtrace('- ' . $type . ': ' . $folder);
}

if ($result['missing']) {
    mtrace('');
    mtrace(
        '[WARNING] Missing: ' .
        implode(', ', $result['missing'])
    );
} else {
    mtrace('');
    mtrace('[OK] Required folders resolved.');
}