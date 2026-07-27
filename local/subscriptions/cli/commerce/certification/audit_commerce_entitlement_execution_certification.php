<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementExecutionCertificationAuditor;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementLedgerShadowAuditor;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementPlanningAuditor;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
use local_subscriptions\commerce\entitlement\planning\CommerceEntitlementGrantPlanner;

[$options] = cli_get_params(
    [
        'strict' => false,
        'json' => false,
        'lang' => 'fr',
        'help' => false,
    ],
    ['h' => 'help']
);

if ($options['help']) {
    echo "Phase 7.94D9 Native entitlement execution certification.\n\n";
    echo "Options:\n  --strict\n  --json\n  --lang=fr\n";
    exit(0);
}

$factory = new CommerceCatalogFactory($DB);
$planner = new CommerceEntitlementGrantPlanner();
$repository = new CommerceEntitlementGrantRepository(
    $DB,
    new CommerceEntitlementGrantRecordMapper()
);
$planning = new CommerceEntitlementPlanningAuditor(
    $DB,
    $factory->purchase_preparation_service(),
    $planner
);
$ledger = new CommerceEntitlementLedgerShadowAuditor(
    $DB,
    $factory->purchase_preparation_service(),
    $planner,
    $repository
);
$report = (new CommerceEntitlementExecutionCertificationAuditor(
    $DB,
    $planning,
    $ledger
))->audit((string)$options['lang']);

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    cli_heading('Phase 7.94D9 - Native entitlement execution certification');
    cli_writeln('checked:           ' . $report['checked']);
    cli_writeln('planned:           ' . $report['planned']);
    cli_writeln('ledger grants:     ' . $report['ledgergrants']);
    cli_writeln('different:         ' . $report['different']);
    cli_writeln('conflict:          ' . $report['conflict']);
    cli_writeln('unsupported types: ' . count($report['unsupportedtypes']));
    cli_writeln('errors:            ' . count($report['errors']));
    cli_writeln('certified:         ' . ($report['certified'] ? 'yes' : 'no'));
}

exit($options['strict'] && !$report['certified'] ? 1 : 0);
