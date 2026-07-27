<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\services\InboxAiServiceFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'threadid' => 0,
        'messageid' => 0,
        'force' => false,
    ],
    [
        'h' => 'help',
        't' => 'threadid',
        'm' => 'messageid',
        'f' => 'force',
    ]
);

if ($options['help']) {
    echo <<<HELP
Tests the CRM Inbox AI orchestrator.

Options:
--threadid=ID     Existing Inbox thread ID
--messageid=ID    Optional existing Inbox message ID
--force           Ignore an existing cached result
-h, --help        Display this help

HELP;
    exit(0);
}

$threadid = (int)$options['threadid'];

if ($threadid <= 0) {
    cli_error(
        'A valid --threadid parameter is required.'
    );
}

$messageid = (int)$options['messageid'];

$content =
    'Bonjour, mon paiement a été effectué mais je ne peux toujours pas accéder au cours. C’est urgent.';

$orchestrator =
    InboxAiServiceFactory::orchestrator();

$request = new InboxAiRequest(
    InboxAiCapability::CATEGORIZATION,
    $threadid,
    $messageid > 0 ? $messageid : null,
    $content,
    'fr',
    [],
    [],
    null
);

$result = $orchestrator->analyse(
    $request,
    !empty($options['force'])
);

mtrace(
    'Status: ' . $result->status
);

mtrace(
    'Provider: ' . $result->provider
);

mtrace(
    'Data: ' .
    json_encode(
        $result->data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);

mtrace(
    'Metadata: ' .
    json_encode(
        $result->metadata,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);