<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\crm\help\validation\HelpCenterValidator;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);

    cli_error(
        "Unknown options:\n  {$unrecognized}"
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Validate the CampusFR CRM Help Center.

Options:
--strict, -s   Return an error code when warnings are detected.
--help, -h     Display this help.

Example:
php local/subscriptions/cli/validate_crm_help.php
php local/subscriptions/cli/validate_crm_help.php --strict

HELP;

    exit(0);
}

$result = (new HelpCenterValidator())->validate();

foreach ($result->successes() as $message) {
    cli_writeln('[OK] ' . $message);
}

foreach ($result->warnings() as $message) {
    cli_writeln('[WARNING] ' . $message);
}

foreach ($result->errors() as $message) {
    cli_writeln('[ERROR] ' . $message);
}

cli_writeln('');
cli_writeln(
    sprintf(
        'Results: %d success(es), %d warning(s), %d error(s).',
        $result->success_count(),
        $result->warning_count(),
        $result->error_count()
    )
);

if ($result->has_errors()) {
    exit(1);
}

if (
    !empty($options['strict']) &&
    $result->warning_count() > 0
) {
    exit(2);
}

exit(0);