<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationBatchLimits;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationRunRepository;
use local_subscriptions\crm\intelligence\recommendations\operations\services\RecommendationBatchRunner;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'limit' =>
            RecommendationBatchLimits::
                DEFAULT_USER_LIMIT,
        'reset-cursor' => false,
        'no-lock' => false,
        'json' => false,
    ],
    [
        'h' => 'help',
        'l' => 'limit',
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
Run the CRM Recommendation Engine batch.

Options:
--limit=N          Maximum users to process.
--reset-cursor     Restart candidate traversal from the beginning.
--no-lock          Run without acquiring the Recommendation Engine lock.
--json             Print the complete JSON report.
-h, --help         Display this help.

Examples:
php local/subscriptions/cli/crm/recommendations/run_crm_recommendations.php
php local/subscriptions/cli/crm/recommendations/run_crm_recommendations.php --limit=50 --json
php local/subscriptions/cli/crm/recommendations/run_crm_recommendations.php --reset-cursor

HELP;

    exit(0);
}

$limit =
    RecommendationBatchLimits::normalize_limit(
        (int)$options['limit']
    );

$lock = null;

if (empty($options['no-lock'])) {
    $lockfactory =
        \core\lock\lock_config::
            get_lock_factory(
                'local_subscriptions_recommendations'
            );

    $lock = $lockfactory->get_lock(
        'recommendation_batch',
        0
    );

    if (!$lock) {
        (new RecommendationRunRepository())
            ->mark_skipped(
                'cli',
                'concurrent_run'
            );

        cli_error(
            'Another Recommendation Engine run is active.'
        );
    }
}

$exitcode = 0;

try {
    $report =
        (new RecommendationBatchRunner())
            ->run(
                limit: $limit,
                source: 'cli',
                resetcursor:
                    !empty(
                        $options['reset-cursor']
                    )
            );

    if (!empty($options['json'])) {
        echo json_encode(
            $report->to_object(),
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_THROW_ON_ERROR
        ) . PHP_EOL;
    } else {
        echo 'Run ID: ' .
            $report->runid .
            PHP_EOL;

        echo 'Status: ' .
            $report->status .
            PHP_EOL;

        echo 'Processed: ' .
            $report->processedcount .
            PHP_EOL;

        echo 'Successful: ' .
            $report->successcount .
            PHP_EOL;

        echo 'Failed: ' .
            $report->failedcount .
            PHP_EOL;

        echo 'Generated recommendations: ' .
            $report->generatedcount .
            PHP_EOL;

        echo 'Persisted recommendations: ' .
            $report->persistedcount .
            PHP_EOL;

        echo 'Correlation matches: ' .
            $report->correlationcount .
            PHP_EOL;

        echo 'Expired recommendations: ' .
            $report->expiredcount .
            PHP_EOL;

        echo 'Cursor: ' .
            $report->startcursor .
            ' -> ' .
            $report->endcursor .
            PHP_EOL;

        echo 'Wrapped: ' .
            ($report->wrapped ? 'yes' : 'no') .
            PHP_EOL;
    }

    $exitcode =
        $report->failedcount > 0
            ? 1
            : 0;
} catch (\Throwable $exception) {
    fwrite(
        STDERR,
        '[ERROR] ' .
        $exception->getMessage() .
        PHP_EOL
    );

    $exitcode = 1;
} finally {
    if ($lock !== null) {
        $lock->release();
        $lock = null;
    }
}

exit($exitcode);