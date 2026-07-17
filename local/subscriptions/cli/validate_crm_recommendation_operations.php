<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;
use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationRunStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationCursorStore;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationRunRepository;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationBatchRunner;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationHealthService;

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
Validate CRM Recommendation Engine operations.

Options:
--strict       Fail on operational warnings.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/validate_crm_recommendation_operations.php --strict

HELP;

    exit(0);
}

$errors = [];
$warnings = [];

$table =
    new xmldb_table(
        'local_subscriptions_recommendation_run'
    );

if (!$DB->get_manager()->table_exists($table)) {
    $errors[] =
        'Recommendation run table is missing.';
} else {
    echo '[OK] Recommendation run table is available.' .
        PHP_EOL;
}

$classes = [
    RecommendationBatchRunner::class,
    RecommendationHealthService::class,
    RecommendationRunRepository::class,
    RecommendationCursorStore::class,
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

if (
    RecommendationBatchLimits::
        DEFAULT_USER_LIMIT <= 0 ||
    RecommendationBatchLimits::
        DATABASE_PAGE_SIZE <= 0
) {
    $errors[] =
        'Recommendation batch limits are invalid.';
} else {
    echo '[OK] Recommendation batch limits validated.' .
        PHP_EOL;
}

foreach (
    RecommendationRunStatus::all()
    as $status
) {
    if (
        !RecommendationRunStatus::
            is_valid($status)
    ) {
        $errors[] =
            'Invalid recommendation run status: ' .
            $status;
    }
}

if ($errors === []) {
    echo '[OK] Recommendation run statuses validated.' .
        PHP_EOL;
}

$taskregistered = false;

foreach (
    \core\task\manager::
        get_all_scheduled_tasks()
    as $task
) {
    if (
        get_class($task) ===
        \local_subscriptions\task\run_crm_recommendations_task::class
    ) {
        $taskregistered = true;
        break;
    }
}

if (!$taskregistered) {
    $warnings[] =
        'Scheduled recommendation task is not registered yet. Run the Moodle upgrade.';
} else {
    echo '[OK] Scheduled recommendation task is registered.' .
        PHP_EOL;
}

$lockfactory =
    \core\lock\lock_config::
        get_lock_factory(
            'local_subscriptions_recommendations'
        );

$lock = $lockfactory->get_lock(
    'recommendation_validation',
    0
);

if (!$lock) {
    $warnings[] =
        'Recommendation lock factory could not acquire a validation lock.';
} else {
    echo '[OK] Recommendation lock factory is operational.' .
        PHP_EOL;

    $lock->release();
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

echo '[OK] CRM Recommendation Engine operations validation completed.' .
    PHP_EOL;

exit(0);