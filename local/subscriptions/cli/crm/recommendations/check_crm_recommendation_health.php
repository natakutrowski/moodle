<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\intelligence\recommendations\operations\dto\RecommendationHealthStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationHealthService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'json' => false,
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
Check CRM Recommendation Engine operational health.

Options:
--json         Print JSON output.
--strict       Return failure for degraded or unhealthy status.
-h, --help     Display this help.

Example:
php local/subscriptions/cli/crm/recommendations/check_crm_recommendation_health.php --strict

HELP;

    exit(0);
}

$health =
    (new RecommendationHealthService())
        ->evaluate();

if (!empty($options['json'])) {
    echo json_encode(
        $health->to_object(),
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_THROW_ON_ERROR
    ) . PHP_EOL;
} else {
    echo 'Status: ' .
        $health->status .
        PHP_EOL;

    echo 'Last run: ' .
        (
            $health->lastrunat !== null
                ? userdate(
                    $health->lastrunat
                )
                : 'never'
        ) .
        PHP_EOL;

    echo 'Last run status: ' .
        ($health->lastrunstatus ?? '-') .
        PHP_EOL;

    echo 'Active recommendations: ' .
        $health->activecount .
        PHP_EOL;

    echo 'Critical recommendations: ' .
        $health->criticalcount .
        PHP_EOL;

    echo 'Waiting expiration: ' .
        $health->dueexpirationcount .
        PHP_EOL;

    echo 'Failed runs in 24h: ' .
        $health->failedruns24h .
        PHP_EOL;

    foreach ($health->warnings as $warning) {
        echo '[WARNING] ' .
            $warning .
            PHP_EOL;
    }

    foreach ($health->errors as $error) {
        echo '[ERROR] ' .
            $error .
            PHP_EOL;
    }
}

if (
    $health->status ===
    RecommendationHealthStatus::UNHEALTHY
) {
    exit(1);
}

if (
    !empty($options['strict']) &&
    $health->status !==
        RecommendationHealthStatus::HEALTHY
) {
    exit(1);
}

exit(0);