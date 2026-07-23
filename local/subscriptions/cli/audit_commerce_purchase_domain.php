<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'limit' => 100,
        'strict' => false,
    ],
    [
        'h' => 'help',
        'l' => 'limit',
        's' => 'strict',
    ]
);

if ($unrecognized !== []) {
    $unrecognized = implode(
        PHP_EOL . '  ',
        $unrecognized
    );

    cli_error(
        'Unknown options:' . PHP_EOL . '  ' . $unrecognized
    );
}

if (!empty($options['help'])) {
    $help = <<<HELP
Audit the Commerce purchase domain.

The command reads recent Legacy-backed purchases and evaluates them through
the unified Commerce purchase model.

No database row is modified.

Options:
-h, --help            Display this help.
-l, --limit=NUMBER    Maximum purchases to inspect. Default: 100.
-s, --strict          Return a failing exit code when incompatibilities exist.

Examples:

php local/subscriptions/cli/audit_commerce_purchase_domain.php

php local/subscriptions/cli/audit_commerce_purchase_domain.php --limit=250

php local/subscriptions/cli/audit_commerce_purchase_domain.php \
    --limit=250 \
    --strict

HELP;

    cli_writeln($help);
    exit(0);
}

$limit = (int)$options['limit'];

if ($limit <= 0) {
    cli_error(
        'The audit limit must be greater than zero.'
    );
}

$limit = min(
    1000,
    $limit
);

$strict = !empty($options['strict']);

$runtime = CommerceRuntimeFactory::create();

$repository =
    $runtime->purchase_domain_repository();

$shadowservice =
    $runtime->purchase_shadow();

$purchases =
    $repository->get_recent(
        $limit
    );

$processed = 0;
$compatible = 0;
$incompatible = 0;
$issuecount = 0;
$errorcount = 0;

cli_heading(
    'Commerce Purchase Domain Audit'
);

cli_writeln(
    'Purchases selected: ' . count($purchases)
);

foreach ($purchases as $purchase) {
    $processed++;

    $report =
        $shadowservice->evaluate(
            $purchase
        );

    if ($report->is_compatible()) {
        $compatible++;

        cli_writeln(
            sprintf(
                '[OK] %s',
                $report->get_purchase_key()
            )
        );

        continue;
    }

    $incompatible++;

    $issues = $report->get_issues();
    $errors = $report->get_errors();

    $issuecount += count($issues);
    $errorcount += count($errors);

    cli_writeln(
        sprintf(
            '[FAIL] %s',
            $report->get_purchase_key()
        )
    );

    foreach ($issues as $issue) {
        cli_writeln(
            sprintf(
                '  ISSUE %-45s %s',
                $issue['code'] ?? 'unknown',
                $issue['message'] ?? ''
            )
        );

        $context = $issue['context'] ?? [];

        if ($context !== []) {
            cli_writeln(
                '        Context: '
                    . json_encode(
                        $context,
                        JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )
            );
        }
    }

    foreach ($errors as $error) {
        cli_writeln(
            sprintf(
                '  ERROR %-45s %s',
                $error['code'] ?? 'unknown',
                $error['message'] ?? ''
            )
        );
    }
}

cli_writeln('');
cli_heading('Summary');

cli_writeln('Processed:    ' . $processed);
cli_writeln('Compatible:   ' . $compatible);
cli_writeln('Incompatible: ' . $incompatible);
cli_writeln('Issues:       ' . $issuecount);
cli_writeln('Errors:       ' . $errorcount);

if ($strict && $incompatible > 0) {
    cli_error(
        'Commerce purchase domain audit failed in strict mode.'
    );
}

cli_writeln('');
cli_writeln('Commerce purchase domain audit complete.');

exit(0);