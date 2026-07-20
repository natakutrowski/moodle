<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerRepository;
use local_subscriptions\crm\user\explorer\UserExplorerTrendFilter;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\trends\DashboardTrendsRepository;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'period' => DashboardPeriod::MONTH,
        'delta' =>
            DashboardTrendsRepository::
                DEFAULT_SIGNIFICANT_DELTA,
        'strict' => false,
    ],
    [
        'h' => 'help',
        'p' => 'period',
        'd' => 'delta',
        's' => 'strict',
    ]
);

if (!empty($unrecognized)) {
    cli_error(
        'Unknown options: '
        . implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    $help = <<<HELP
Validate Phase 7.75E CRM trends.

Options:
--period=today|week|month
    Dashboard period to validate.

--delta=N
    Minimum score variation. Default: 5.

--strict
    Return a non-zero exit code when warnings are found.

-h, --help
    Display this help.

Examples:

php local/subscriptions/cli/validate_crm_trends.php

php local/subscriptions/cli/validate_crm_trends.php \
    --period=month \
    --delta=5 \
    --strict

HELP;

    cli_writeln($help);
    exit(0);
}

$period = DashboardPeriod::normalize(
    (string)$options['period']
);

$delta = max(
    1,
    min(
        100,
        (int)$options['delta']
    )
);

$strict = !empty($options['strict']);

$errors = [];
$warnings = [];

$ok = static function(
    string $message
): void {
    cli_writeln('[OK] ' . $message);
};

$warning = static function(
    string $message
) use (&$warnings): void {
    $warnings[] = $message;
    cli_writeln('[WARNING] ' . $message);
};

$error = static function(
    string $message
) use (&$errors): void {
    $errors[] = $message;
    cli_writeln('[ERROR] ' . $message);
};

cli_writeln(
    'CRM Trends validation'
);

cli_writeln(
    'Period: ' . $period
);

cli_writeln(
    'Delta: ' . $delta
);

cli_writeln(
    str_repeat('-', 60)
);

/*
 * 1. Required classes.
 */
$classes = [
    UserExplorerCriteria::class,
    UserExplorerRepository::class,
    UserExplorerTrendFilter::class,
    DashboardTrendsRepository::class,
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        $ok('Class available: ' . $class);
    } else {
        $error('Missing class: ' . $class);
    }
}

/*
 * 2. Required score-table fields.
 */
$table = 'local_subscriptions_crm_score';

if (!$DB->get_manager()->table_exists($table)) {
    $error(
        'Missing table: ' . $table
    );
} else {
    $ok(
        'Score table is available.'
    );

    $requiredfields = [
        'id',
        'userid',
        'engagementscore',
        'riskscore',
        'globalscore',
        'timecreated',
    ];

    $columns =
        $DB->get_columns($table);

    foreach ($requiredfields as $field) {
        if (isset($columns[$field])) {
            $ok(
                'Score field available: '
                . $field
            );
        } else {
            $error(
                'Missing score field: '
                . $field
            );
        }
    }
}

/*
 * 3. Required translations.
 */
$stringkeys = [
    'crm_trends_title',
    'crm_trends_subtitle',
    'crm_trends_users',
    'crm_trends_previous_value',
    'crm_trends_difference_only',
    'crm_trends_difference_with_percent',
    'crm_trends_stable',
    'crm_trends_open_explorer',
    'crm_trends_freshness',
    'crm_trends_freshness_unknown',
    'crm_trends_no_current_data',
    'crm_trends_insufficient_data',
    'crm_trends_no_movements',
    'crm_trends_error',
    'crm_trends_metric_risk_up',
    'crm_trends_metric_risk_down',
    'crm_trends_metric_engagement_up',
    'crm_trends_metric_engagement_down',
    'crm_trends_metric_global_up',
    'crm_trends_metric_global_down',
    'crm_trends_metric_unknown',
    'crm_trends_metric_open',
    'crm_user_explorer_trend_active',
    'crm_user_explorer_trend_period',
    'crm_user_explorer_trend_threshold',
    'crm_user_explorer_trend_clear',
];

$languages = [
    'fr',
    'en',
    'ru',
];

foreach ($languages as $language) {
    $strings =
        get_string_manager()
            ->load_component_strings(
                'local_subscriptions',
                $language
            );

    foreach ($stringkeys as $key) {
        if (array_key_exists($key, $strings)) {
            $ok(
                "String {$language}: {$key}"
            );
        } else {
            $error(
                "Missing string {$language}: {$key}"
            );
        }
    }
}

/*
 * 4. Resolve the selected Dashboard range.
 */
try {
    $range =
        DashboardPeriod::range($period);

    $start =
        (int)$range['start'];

    $end =
        (int)$range['end'];

    if (
        $start <= 0
        || $end <= $start
    ) {
        $error(
            'Dashboard period returned an invalid range.'
        );
    } else {
        $ok(
            'Dashboard period range: '
            . userdate($start)
            . ' -> '
            . userdate($end)
        );
    }
} catch (\Throwable $exception) {
    $error(
        'Unable to resolve Dashboard period: '
        . $exception->getMessage()
    );

    $start = 0;
    $end = 0;
}

/*
 * 5. Validate each Explorer drill-down.
 */
$trends = [
    DashboardTrendsRepository::
        METRIC_ENGAGEMENT_UP,

    DashboardTrendsRepository::
        METRIC_ENGAGEMENT_DOWN,

    DashboardTrendsRepository::
        METRIC_RISK_UP,

    DashboardTrendsRepository::
        METRIC_RISK_DOWN,

    DashboardTrendsRepository::
        METRIC_GLOBAL_UP,

    DashboardTrendsRepository::
        METRIC_GLOBAL_DOWN,
];

if (
    empty($errors)
    && $start > 0
    && $end > $start
) {
    $repository =
        new UserExplorerRepository();

    foreach ($trends as $trend) {
        $filter =
            UserExplorerTrendFilter::create(
                $trend,
                $start,
                $end,
                $delta
            );

        if (!$filter->is_active()) {
            $error(
                'Trend was rejected by normalization: '
                . $trend
            );

            continue;
        }

        $criteria =
            new UserExplorerCriteria(
                trendfilter: $filter
            );

        $startedat = microtime(true);

        try {
            $count =
                $repository->count(
                    $criteria
                );

            $duration =
                microtime(true)
                - $startedat;

            $ok(
                sprintf(
                    '%s: %d user(s), %.4f seconds',
                    $trend,
                    $count,
                    $duration
                )
            );

            if ($duration > 2.0) {
                $warning(
                    sprintf(
                        '%s count query is slow: %.4f seconds',
                        $trend,
                        $duration
                    )
                );
            }

            /*
             * Validate the records path too.
             */
            $recordsstartedat =
                microtime(true);

            $records =
                $repository->get_records(
                    $criteria,
                    false
                );

            $recordsduration =
                microtime(true)
                - $recordsstartedat;

            $ok(
                sprintf(
                    '%s records: %d row(s), %.4f seconds',
                    $trend,
                    count($records),
                    $recordsduration
                )
            );

            if ($recordsduration > 2.0) {
                $warning(
                    sprintf(
                        '%s records query is slow: %.4f seconds',
                        $trend,
                        $recordsduration
                    )
                );
            }

            if (count($records) > $count) {
                $error(
                    $trend
                    . ': page records exceed total count.'
                );
            }
        } catch (\Throwable $exception) {
            $error(
                $trend
                . ': '
                . $exception->getMessage()
            );
        }
    }
}

/*
 * 6. Score history availability.
 */
if (
    $DB->get_manager()->table_exists($table)
) {
    $totalscores =
        $DB->count_records($table);

    $userswithhistory =
        $DB->count_records_sql("
            SELECT COUNT(*)
              FROM (
                    SELECT userid
                      FROM {{$table}}
                  GROUP BY userid
                    HAVING COUNT(*) >= 2
              ) historyusers
        ");

    $ok(
        'Total score snapshots: '
        . $totalscores
    );

    $ok(
        'Users with at least two snapshots: '
        . $userswithhistory
    );

    if ($totalscores === 0) {
        $warning(
            'No score snapshots exist yet.'
        );
    } else if ($userswithhistory === 0) {
        $warning(
            'Snapshots exist, but no user has a comparable history yet.'
        );
    }
}

/*
 * Final status.
 */
cli_writeln(
    str_repeat('-', 60)
);

cli_writeln(
    sprintf(
        'Validation completed: %d error(s), %d warning(s).',
        count($errors),
        count($warnings)
    )
);

if (!empty($errors)) {
    exit(1);
}

if ($strict && !empty($warnings)) {
    exit(2);
}

exit(0);