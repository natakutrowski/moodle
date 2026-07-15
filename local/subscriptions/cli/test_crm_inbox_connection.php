<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\crm\inbox\connectors\imap\ImapMimeParser;
use local_subscriptions\crm\inbox\connectors\imap\OvhImapConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxAccountService;

$repository = new InboxAccountRepository();

$account = $repository->find_by_email(
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

$service = new InboxAccountService(
    $repository,
    $credentials
);

$service->assert_ready($account);

$connector = new OvhImapConnector(
    $credentials,
    new ImapMimeParser()
);

mtrace('Testing IMAP connection...');

$connector->test_connection($account);

mtrace('Connection successful.');
mtrace('');
mtrace('Available folders:');

foreach ($connector->list_folders($account) as $folder) {
    mtrace(
        '- ' .
        $folder->name .
        (
            $folder->specialuse !== null
                ? ' [' . $folder->specialuse . ']'
                : ''
        )
    );
}