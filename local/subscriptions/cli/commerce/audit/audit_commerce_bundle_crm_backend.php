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
    echo "Phase 7.94E3 unified CRM Commerce product backend audit.\n\n";
    echo "Options:\n";
    echo "  --details  Show one JSON line per product.\n";
    echo "  --strict   Return a non-zero exit code when certification fails.\n";
    echo "  --json     Return the full report as JSON.\n";
    exit(0);
}

$report = (new CommerceCatalogFactory($DB))->bundle_crm_backend_auditor()->audit();

if ($options['json']) {
    echo json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} else {
    cli_heading('Phase 7.94E3 - Unified CRM Commerce product backend');
    cli_writeln('products:           ' . $report['products']);
    cli_writeln('bundles:            ' . $report['bundles']);
    cli_writeln('prices:             ' . $report['prices']);
    cli_writeln('translations:       ' . $report['translations']);
    cli_writeln('components:         ' . $report['components']);
    cli_writeln('entitlements:       ' . $report['entitlements']);
    cli_writeln('bundles previewed:  ' . $report['previewed']);
    cli_writeln('bundle registered:  ' . ($report['bundletyperegistered'] ? 'yes' : 'no'));
    cli_writeln('errors:             ' . count($report['errors']));
    cli_writeln('certified:          ' . ($report['certified'] ? 'yes' : 'no'));

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
