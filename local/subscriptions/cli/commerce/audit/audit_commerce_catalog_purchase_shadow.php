<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;

[$options] = cli_get_params(
    ['details' => false, 'strict' => false, 'json' => false, 'lang' => 'fr', 'help' => false],
    ['h' => 'help']
);

if ($options['help']) {
    echo "Phase 7.94C3 Native catalogue purchase shadow audit.\n\n";
    echo "Options:\n  --details\n  --strict\n  --json\n  --lang=fr\n";
    exit(0);
}

$report = (new CommerceCatalogFactory($DB))->purchase_shadow_auditor()->audit((string)$options['lang']);

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    cli_heading('Phase 7.94C3 - Native catalogue purchase shadow');
    cli_writeln('checked:    ' . $report['checked']);
    cli_writeln('equal:      ' . $report['equal']);
    cli_writeln('different:  ' . $report['different']);
    cli_writeln('errors:     ' . count($report['errors']));
    if ($options['details']) {
        foreach ($report['details'] as $detail) {
            cli_writeln(json_encode($detail, JSON_UNESCAPED_SLASHES));
        }
        foreach ($report['errors'] as $error) {
            cli_writeln('ERROR: ' . $error);
        }
    }
}

exit($options['strict'] && ($report['different'] > 0 || $report['errors'] !== []) ? 1 : 0);
