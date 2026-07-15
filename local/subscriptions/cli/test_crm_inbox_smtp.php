<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\crm\inbox\connectors\smtp\OvhSmtpConnector;
use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;

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

$connector = new OvhSmtpConnector(
    $credentials
);

mtrace('Testing OVH SMTP connection...');

$connector->test_connection($account);

mtrace('SMTP connection successful.');