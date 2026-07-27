<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\services\InboxSyncRuntimeFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'account' => 0,
        'folder' => 'INBOX',
        'batchsize' => 50,
        'all' => false,
    ],
    [
        'h' => 'help',
        'a' => 'account',
        'f' => 'folder',
        'b' => 'batchsize',
    ]
);

if ($unrecognized) {
    $unrecognizedlist = implode(
        PHP_EOL . '  ',
        $unrecognized
    );

    cli_error(
        'Options non reconnues :' .
        PHP_EOL .
        '  ' .
        $unrecognizedlist
    );
}

if ($options['help']) {
    echo <<<HELP
Synchronise la CRM Inbox.

Options:
--account=ID       Identifiant du compte Inbox
--folder=INBOX     Dossier IMAP
--batchsize=50     Nombre de messages par page
--all              Continue jusqu'à la fin de l'historique
-h, --help         Affiche cette aide

HELP;

    exit(0);
}

$runtime = (
    new InboxSyncRuntimeFactory()
)->create_runtime();

$account = !empty($options['account'])
    ? $runtime->accounts->find(
        (int)$options['account']
    )
    : $runtime->accounts->find_by_email(
        'support@campusfr.fr'
    );

if (!$account) {
    throw new moodle_exception(
        'crm_inbox_account_not_found',
        'local_subscriptions'
    );
}

$folder = trim(
    (string)$options['folder']
);

if ($folder === '') {
    cli_error(
        'Le dossier IMAP ne peut pas être vide.'
    );
}

$batchsize = max(
    1,
    min(
        200,
        (int)$options['batchsize']
    )
);

do {
    $result = $runtime->sync->sync_folder(
        $account,
        $folder,
        $batchsize
    );

    mtrace(
        'Fetched: ' .
        $result->fetched
    );

    mtrace(
        'Created: ' .
        $result->created
    );

    mtrace(
        'Updated: ' .
        $result->updated
    );

    mtrace(
        'Skipped: ' .
        $result->skipped
    );

    mtrace(
        'Errors: ' .
        $result->errors
    );

    mtrace(
        'More: ' .
        (
            $result->hasmore
                ? 'yes'
                : 'no'
        )
    );

    $account =
        $runtime->accounts->find(
            $account->id
        );

    if (!$account) {
        throw new moodle_exception(
            'crm_inbox_account_not_found',
            'local_subscriptions'
        );
    }
} while (
    !empty($options['all']) &&
    $result->hasmore &&
    $result->errors === 0
);