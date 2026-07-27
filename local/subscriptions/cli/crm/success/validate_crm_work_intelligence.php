<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\intelligence\services\WorkItemSuggestionPolicy;
use local_subscriptions\crm\work\intelligence\services\WorkTeamSuggestionService;

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
Validate CRM Work Management intelligence.

Options:
--strict       Fail on warnings.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/crm/success/validate_crm_work_intelligence.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$requiredclasses = [
    \local_subscriptions\crm\work\intelligence\dto\WorkItemSuggestion::class,
    \local_subscriptions\crm\work\intelligence\dto\WorkItemDuplicateCandidate::class,
    \local_subscriptions\crm\work\intelligence\dto\WorkTeamSuggestion::class,
    \local_subscriptions\crm\work\intelligence\services\WorkItemDuplicateDetector::class,
    \local_subscriptions\crm\work\intelligence\services\WorkTeamSuggestionService::class,
    \local_subscriptions\crm\work\intelligence\services\WorkItemSuggestionService::class,
    \local_subscriptions\crm\work\intelligence\services\SuggestedWorkItemCreationService::class,
];

foreach ($requiredclasses as $class) {
    if (!class_exists($class)) {
        $errors[] =
            'Missing class: ' . $class;
        continue;
    }

    echo '[OK] Class available: ' .
        $class .
        PHP_EOL;
}

$policy =
    new WorkItemSuggestionPolicy();

$expectedtypes = [
    WorkItemType::TASK,
    WorkItemType::SUPPORT,
    WorkItemType::FINANCE,
    WorkItemType::FOLLOW_UP,
];

foreach ($expectedtypes as $type) {
    if (!WorkItemType::is_valid($type)) {
        $errors[] =
            'Invalid Work Item type constant: ' .
            $type;
    }
}

foreach (WorkItemPriority::all() as $priority) {
    if (
        !WorkItemPriority::is_valid(
            $priority
        )
    ) {
        $errors[] =
            'Invalid Work Item priority: ' .
            $priority;
    }
}

$teams =
    (new WorkTeamSuggestionService())
        ->suggest(
            WorkItemType::SUPPORT,
            'review_support_situation',
            [
                'inbox',
                'customer_success',
            ]
        );

if ($teams === []) {
    $warnings[] =
        'No enabled Work Team could be suggested.';
} else {
    echo '[OK] ' .
        count($teams) .
        ' Work Team suggestion(s) available.' .
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

echo '[OK] CRM Work Management intelligence validation completed.' .
    PHP_EOL;

exit(0);