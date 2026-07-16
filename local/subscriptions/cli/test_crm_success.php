<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\success\services\CustomerSuccessRuntimeFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'userid' => 0,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
        'v' => 'verbose',
    ]
);

if ($unrecognized) {
    $unrecognized = implode(
        "\n  ",
        $unrecognized
    );

    cli_error(
        "Unknown options:\n  " . $unrecognized
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Customer Success diagnostic.

Options:
-u, --userid=ID    Moodle user ID to evaluate.
-v, --verbose      Display metrics and signals.
-h, --help         Display this help.

Example:
php local/subscriptions/cli/test_crm_success.php --userid=2 --verbose

HELP;

    exit(0);
}

$userid = (int)$options['userid'];

if ($userid <= 0) {
    cli_error(
        'A valid --userid value is required.'
    );
}

$user = $DB->get_record(
    'user',
    [
        'id' => $userid,
        'deleted' => 0,
    ],
    implode(',', [
        'id',
        'username',
        'firstname',
        'lastname',
        'firstnamephonetic',
        'lastnamephonetic',
        'middlename',
        'alternatename',
        'email',
        'lastaccess',
        'suspended',
    ]),
    IGNORE_MISSING
);

if (!$user) {
    cli_error(
        'Moodle user not found.'
    );
}

$factory =
    new CustomerSuccessRuntimeFactory();

$runtime =
    $factory->create();

$result =
    $runtime->evaluate($userid);

echo 'User: ' .
    fullname($user) .
    ' (#' .
    $userid .
    ')' .
    PHP_EOL;

echo 'Successful: ' .
    ($result->is_successful() ? 'yes' : 'no') .
    PHP_EOL;

echo PHP_EOL;
echo "Collectors:\n";

foreach (
    $result->collection->executedcollectors
    as $collector
) {
    echo '- ' . $collector . ': executed' . PHP_EOL;
}

foreach (
    $result->collection->unavailablecollectors
    as $collector
) {
    echo '- ' . $collector . ': unavailable' . PHP_EOL;
}

echo PHP_EOL;

echo 'Metrics: ' .
    $result->collection->metrics->count() .
    PHP_EOL;

echo 'Signals: ' .
    $result->signals->count() .
    PHP_EOL;

echo 'Global score: ' .
    (
        $result->score->global !== null
            ? (string)$result->score->global
            : 'unavailable'
    ) .
    PHP_EOL;

echo 'Health level: ' .
    (
        $result->score->level !== null
            ? $result->score->level
            : 'unavailable'
    ) .
    PHP_EOL;

echo 'Risk exposure: ' .
    (
        $result->score->risk_exposure() !== null
            ? (string)$result->score->risk_exposure()
            : 'unavailable'
    ) .
    PHP_EOL;

echo PHP_EOL;
echo "Dimensions:\n";

foreach ($result->score->dimensions() as $dimension) {
    echo '- ' .
        $dimension->category .
        ': ' .
        (
            $dimension->score !== null
                ? (string)$dimension->score
                : 'unavailable'
        ) .
        ' (' .
        (
            $dimension->level !== null
                ? $dimension->level
                : 'no data'
        ) .
        ')' .
        PHP_EOL;
}

if ($result->collection->errors !== []) {
    echo PHP_EOL;
    echo "Collector errors:\n";

    foreach (
        $result->collection->errors
        as $collector => $message
    ) {
        echo '- ' .
            $collector .
            ': ' .
            $message .
            PHP_EOL;
    }
}

if ($result->signalerrors !== []) {
    echo PHP_EOL;
    echo "Signal errors:\n";

    foreach (
        $result->signalerrors
        as $rule => $message
    ) {
        echo '- ' .
            $rule .
            ': ' .
            $message .
            PHP_EOL;
    }
}

if (!empty($options['verbose'])) {
    echo PHP_EOL;
    echo "Metrics:\n";

    foreach (
        $result->collection->metrics
        as $metric
    ) {
        echo '- [' .
            $metric->source .
            '] ' .
            $metric->key .
            ' = ' .
            format_value($metric->value) .
            PHP_EOL;
    }

    echo PHP_EOL;
    echo "Signals:\n";

    foreach ($result->signals as $signal) {
        echo '- [' .
            $signal->category .
            '] ' .
            $signal->key .
            ' = ' .
            sprintf(
                '%+d',
                $signal->weight
            ) .
            ' (' .
            $signal->polarity .
            ')' .
            PHP_EOL;
    }
}

/**
 * Formats a scalar diagnostic value.
 *
 * @param int|float|string|bool|null $value
 */
function format_value(
    int|float|string|bool|null $value
): string {
    if ($value === null) {
        return 'null';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    return (string)$value;
}