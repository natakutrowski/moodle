<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementPlanningAuditor;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;

[$options] = cli_get_params(
    [
        'details' => false,
        'strict' => false,
        'json' => false,
        'lang' => 'fr',
        'help' => false,
    ],
    ['h' => 'help']
);

if ($options['help']) {
    echo "Phase 7.94D3 Native entitlement planning audit.\n\n";
    echo "Options:\n  --details\n  --strict\n  --json\n  --lang=fr\n";
    exit(0);
}

$factory = new CommerceCatalogFactory($DB);
$auditor = new CommerceEntitlementPlanningAuditor(
    $DB,
    $factory->purchase_preparation_service(),
    new CommerceEntitlementGrantPlanner()
);
$report = $auditor->audit((string)$options['lang']);

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    cli_heading('Phase 7.94D3 - Native entitlement planning');
    cli_writeln('checked:      ' . $report['checked']);
    cli_writeln('equal:        ' . $report['equal']);
    cli_writeln('different:    ' . $report['different']);
    cli_writeln('definitions:  ' . $report['definitions']);
    cli_writeln('planned:      ' . $report['planned']);
    cli_writeln('errors:       ' . count($report['errors']));

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
