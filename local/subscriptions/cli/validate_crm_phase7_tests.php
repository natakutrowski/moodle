<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options) = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    cli_writeln(
        'Validate Phase 7 PHPUnit test definitions.'
    );

    cli_writeln(
        ''
    );

    cli_writeln(
        'Options:'
    );

    cli_writeln(
        '  --strict       Treat warnings as errors.'
    );

    cli_writeln(
        '  -h, --help     Show this help.'
    );

    exit(0);
}

$strict = !empty(
    $options['strict']
);

$pluginroot =
    $CFG->dirroot .
    '/local/subscriptions';

$tests = [
    'tests/subscription_config_test.php' => [
        'class' =>
            'subscription_config_test',

        'base' =>
            'advanced_testcase',
    ],

    'tests/crm/success/plans/status_test.php' => [
        'class' =>
            'status_test',

        'base' =>
            'basic_testcase',
    ],

    'tests/crm/success/plans/' .
        'dependency_state_service_test.php' => [
        'class' =>
            'dependency_state_service_test',

        'base' =>
            'basic_testcase',
    ],

    'tests/crm/success/plans/' .
        'customer_success_plan_step_test.php' => [
        'class' =>
            'customer_success_plan_step_test',

        'base' =>
            'basic_testcase',
    ],

    'tests/crm/success/plans/presentation_test.php' => [
        'class' =>
            'presentation_test',

        'base' =>
            'advanced_testcase',
    ],
];

$errors = [];
$warnings = [];

foreach ($tests as $relativepath => $definition) {
    $path =
        $pluginroot .
        '/' .
        $relativepath;

    if (!is_file($path)) {
        $errors[] =
            'Missing PHPUnit test file: ' .
            $relativepath;

        continue;
    }

    $source = file_get_contents(
        $path
    );

    if ($source === false) {
        $errors[] =
            'Unable to read PHPUnit test file: ' .
            $relativepath;

        continue;
    }

    $classpattern =
        '/final\s+class\s+' .
        preg_quote(
            $definition['class'],
            '/'
        ) .
        '\s+extends\s+' .
        preg_quote(
            $definition['base'],
            '/'
        ) .
        '\b/';

    if (
        preg_match(
            $classpattern,
            $source
        ) !== 1
    ) {
        $errors[] =
            sprintf(
                '%s must declare final class %s extending %s.',
                $relativepath,
                $definition['class'],
                $definition['base']
            );
    }

    if (
        preg_match(
            '#/var/www/|/var/moodledata|/home/#',
            $source
        )
    ) {
        $errors[] =
            'Server-specific absolute path found in ' .
            $relativepath;
    }

    if (
        preg_match(
            '/\b(?:sleep|usleep)\s*\(/',
            $source
        )
    ) {
        $warnings[] =
            'Timing-dependent call found in ' .
            $relativepath;
    }

    if (
        preg_match(
            '/\bglobal\s+\$DB\b|' .
            '\$DB\s*->/',
            $source
        )
    ) {
        $warnings[] =
            'Direct database use found in test: ' .
            $relativepath;
    }
}

foreach ($warnings as $warning) {
    cli_writeln(
        '[WARNING] ' .
        $warning
    );
}

foreach ($errors as $error) {
    cli_writeln(
        '[ERROR] ' .
        $error
    );
}

if (
    $strict &&
    $warnings !== []
) {
    $errors = array_merge(
        $errors,
        $warnings
    );
}

if ($errors !== []) {
    cli_error(
        sprintf(
            'Phase 7 test validation failed with %d error(s).',
            count($errors)
        )
    );
}

cli_writeln(
    '[OK] Required Phase 7 PHPUnit test files are present.'
);

cli_writeln(
    '[OK] Phase 7 PHPUnit test classes use the expected base cases.'
);

cli_writeln(
    '[OK] No server-specific path was found in Phase 7 tests.'
);

cli_writeln(
    '[OK] Phase 7 test definition audit passed.'
);

exit(0);