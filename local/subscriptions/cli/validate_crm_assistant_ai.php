<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\assistant\ai\prompts\CrmAssistantPromptBuilder;
use local_subscriptions\crm\assistant\ai\prompts\CrmAssistantSchema;
use local_subscriptions\crm\assistant\ai\services\CrmAssistantAiRuntimeFactory;
use local_subscriptions\crm\assistant\ai\services\CrmAssistantConversationService;
use local_subscriptions\crm\assistant\ai\services\CrmAssistantContextBuilder;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' .
        implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Validate the conversational CRM Assistant.

Options:
--strict       Fail when the OpenAI provider is unavailable.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/validate_crm_assistant_ai.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$classes = [
    CrmAssistantPromptBuilder::class,
    CrmAssistantSchema::class,
    CrmAssistantContextBuilder::class,
    CrmAssistantConversationService::class,
    CrmAssistantAiRuntimeFactory::class,
];

foreach ($classes as $class) {
    if (!class_exists($class)) {
        $errors[] =
            'Missing class: ' . $class;
        continue;
    }

    echo '[OK] Class available: ' .
        $class .
        PHP_EOL;
}

$schema =
    (new CrmAssistantSchema())
        ->schema();

if (
    ($schema['type'] ?? null) !==
        'object' ||
    empty($schema['properties']) ||
    empty($schema['required'])
) {
    $errors[] =
        'CRM Assistant Structured Output schema is invalid.';
} else {
    echo '[OK] CRM Assistant Structured Output schema validated.' .
        PHP_EOL;
}

$provider =
    (new CrmAssistantAiRuntimeFactory())
        ->provider();

if (!$provider->available()) {
    $warnings[] =
        'OpenAI provider is unavailable.';
} else {
    echo '[OK] OpenAI provider is available.' .
        PHP_EOL;
}

foreach ($warnings as $warning) {
    echo '[WARNING] ' .
        $warning .
        PHP_EOL;
}

foreach ($errors as $error) {
    echo '[ERROR] ' .
        $error .
        PHP_EOL;
}

if (
    $errors !== [] ||
    (
        !empty($options['strict']) &&
        $warnings !== []
    )
) {
    exit(1);
}

echo '[OK] Conversational CRM Assistant validation completed.' .
    PHP_EOL;

exit(0);