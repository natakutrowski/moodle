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
        'capability' =>
            InboxAiCapability::LANGUAGE_DETECTION,
        'message' =>
            'Bonjour, je ne peux pas accéder à mon cours.',
        'language' => 'fr',
    ],
    [
        'h' => 'help',
        'c' => 'capability',
        'm' => 'message',
        'l' => 'language',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if ($options['help']) {
    echo <<<HELP
Test the CRM Inbox OpenAI provider.

--capability=language_detection
--message="Text to analyse"
--language=fr

HELP;
    exit(0);
}

$provider =
    InboxAiServiceFactory::openai_provider();

mtrace(
    'Available: ' .
    ($provider->is_available()
        ? 'yes'
        : 'no')
);

if (!$provider->is_available()) {
    cli_error(
        'OpenAI is not fully configured.'
    );
}

$result = $provider->analyse(
    new InboxAiRequest(
        (string)$options['capability'],
        0,
        null,
        (string)$options['message'],
        (string)$options['language'],
        [],
        [],
        null
    )
);

mtrace('Status: ' . $result->status);
mtrace('Provider: ' . $result->provider);
mtrace('Model: ' . ($result->model ?? '-'));
mtrace(
    'Confidence: ' .
    number_format($result->confidence, 3)
);

mtrace(
    json_encode(
        $result->data,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);

mtrace(
    'Metadata: ' .
    json_encode(
        $result->metadata,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    )
);