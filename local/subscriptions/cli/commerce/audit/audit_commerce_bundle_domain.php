<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options, $unrecognised] = cli_get_params(
    [
        'details' => false,
        'strict' => false,
        'json' => false,
        'help' => false,
    ],
    [
        'd' => 'details',
        's' => 'strict',
        'j' => 'json',
        'h' => 'help',
    ]
);

if ($unrecognised !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}

if ($options['help']) {
    echo "Phase 7.94E1 Commerce bundle domain audit.\n\n";
    echo "Options:\n";
    echo "  --details  Show one JSON line per bundle.\n";
    echo "  --strict   Return a non-zero exit code when certification fails.\n";
    echo "  --json     Return the full report as JSON.\n";
    exit(0);
}

$report = (new CommerceCatalogFactory($DB))->bundle_domain_auditor()->audit();

if ($options['json']) {
    echo json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} else {
    cli_heading('Phase 7.94E1 - Commerce bundle domain');
    cli_writeln('bundles:          ' . $report['bundles']);
    cli_writeln('components:       ' . $report['components']);
    cli_writeln('empty:            ' . $report['empty']);
    cli_writeln('unknown:          ' . $report['unknown']);
    cli_writeln('disabled:         ' . $report['disabled']);
    cli_writeln('duplicates:       ' . $report['duplicates']);
    cli_writeln('self references:  ' . $report['selfreferences']);
    cli_writeln('cycles:           ' . $report['cycles']);
    cli_writeln('registered types: ' . count($report['registeredtypes']));
    cli_writeln('errors:           ' . count($report['errors']));
    cli_writeln('certified:        ' . ($report['certified'] ? 'yes' : 'no'));

    if ($options['details']) {
        foreach ($report['details'] as $detail) {
            cli_writeln(json_encode(
                $detail,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
        }

        foreach ($report['errors'] as $error) {
            cli_writeln('ERROR: ' . $error);
        }
    }
}

exit($options['strict'] && !$report['certified'] ? 1 : 0);
