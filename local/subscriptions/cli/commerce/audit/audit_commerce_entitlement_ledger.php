<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\entitlement\audit\CommerceEntitlementLedgerShadowAuditor;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRecordMapper;
use local_subscriptions\commerce\entitlement\persistence\CommerceEntitlementGrantRepository;
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
    echo "Phase 7.94D6 Native entitlement ledger shadow audit.\n\n";
    echo "Options:\n  --details\n  --strict\n  --json\n  --lang=fr\n";
    exit(0);
}

$factory = new CommerceCatalogFactory($DB);
$mapper = new CommerceEntitlementGrantRecordMapper();
$repository = new CommerceEntitlementGrantRepository($DB, $mapper);
$auditor = new CommerceEntitlementLedgerShadowAuditor(
    $DB,
    $factory->purchase_preparation_service(),
    new CommerceEntitlementGrantPlanner(),
    $repository
);
$report = $auditor->audit((string)$options['lang']);

if ($options['json']) {
    echo json_encode(
        $report,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . PHP_EOL;
} else {
    cli_heading('Phase 7.94D6 - Native entitlement ledger shadow');
    cli_writeln('checked:    ' . $report['checked']);
    cli_writeln('grants:     ' . $report['grants']);
    cli_writeln('create:     ' . $report['create']);
    cli_writeln('identical:  ' . $report['identical']);
    cli_writeln('conflict:   ' . $report['conflict']);
    cli_writeln('errors:     ' . count($report['errors']));

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

$failed = $report['conflict'] > 0 || $report['errors'] !== [];
exit($options['strict'] && $failed ? 1 : 0);
