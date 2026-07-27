<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxAccountService;

$enabled = in_array(
    '--enable',
    $argv,
    true
);

$repository = new InboxAccountRepository();
$credentials =
    new MoodleConfigInboxCredentialStore();

$service = new InboxAccountService(
    $repository,
    $credentials
);

$account = $service
    ->configure_ovh_support_account(
        $enabled
    );

mtrace('CRM Inbox account configured.');
mtrace('ID: ' . $account->id);
mtrace('Email: ' . $account->email);
mtrace(
    'Enabled: ' .
    ($account->enabled ? 'yes' : 'no')
);

if (
    $account->credentialkey !== null &&
    $credentials->has(
        $account->credentialkey
    )
) {
    mtrace('Credentials: available');
} else {
    mtrace('Credentials: missing');
}
