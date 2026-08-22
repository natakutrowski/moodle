<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\credentials\MoodleConfigInboxCredentialStore;
use local_subscriptions\crm\inbox\repositories\InboxAccountRepository;
use local_subscriptions\crm\inbox\services\InboxAccountService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'enable' => false,
        'email' => 'support@campusfr.fr',
        'name' => 'CampusFR Support',
        'credentialkey' => 'support_ovh',
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown option(s): ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Configure one CRM Inbox OVH IMAP/SMTP account.

Options:
  --email=ADDRESS          Mailbox email address.
  --name=NAME              Human-readable account name.
  --credentialkey=KEY      Key in \$CFG->local_subscriptions_inbox_credentials.
  --enable                 Enable the account after configuration.
  -h, --help               Show this help.

Examples:
  php local/subscriptions/cli/crm/inbox/configure_crm_inbox_account.php \\
    --email=noreply@campusfr.fr \\
    --name="CampusFR DEV Inbox" \\
    --credentialkey=noreply_ovh \\
    --enable

  php local/subscriptions/cli/crm/inbox/configure_crm_inbox_account.php \\
    --email=support@campusfr.fr \\
    --name="CampusFR Support" \\
    --credentialkey=support_ovh \\
    --enable

HELP;
    exit(0);
}

$email = trim((string)$options['email']);
$name = trim((string)$options['name']);
$credentialkey = trim(
    (string)$options['credentialkey']
);
$enabled = !empty($options['enable']);

if ($email === '' || !validate_email($email)) {
    cli_error('A valid --email address is required.');
}

if ($name === '') {
    cli_error('--name cannot be empty.');
}

if ($credentialkey === '') {
    cli_error('--credentialkey cannot be empty.');
}

$repository = new InboxAccountRepository();
$credentials = new MoodleConfigInboxCredentialStore();
$service = new InboxAccountService(
    $repository,
    $credentials
);

$account = $service->configure_ovh_account(
    $name,
    $email,
    $credentialkey,
    $enabled
);

mtrace('CRM Inbox account configured.');
mtrace('ID: ' . $account->id);
mtrace('Name: ' . $account->name);
mtrace('Email: ' . $account->email);
mtrace('Credential key: ' . ($account->credentialkey ?? '-'));
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
