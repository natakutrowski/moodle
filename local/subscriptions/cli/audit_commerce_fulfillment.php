<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\fulfillment\shadow\CommerceFulfillmentShadowService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'limit' => 100, 'strict' => false],
    ['h' => 'help', 'l' => 'limit', 's' => 'strict']
);

if ($unrecognized !== []) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    cli_writeln('Read-only Commerce fulfillment audit.');
    cli_writeln('--limit=NUMBER  Maximum purchases, default 100.');
    cli_writeln('--strict        Fail on incompatible or unfulfilled purchases.');
    exit(0);
}

$limit = max(1, min(1000, (int)$options['limit']));
$runtime = CommerceRuntimeFactory::create();
$purchases = $runtime->purchase_domain_repository()->get_recent($limit);
$service = new CommerceFulfillmentShadowService();
$reports = $service->inspect_many($purchases);

$fulfilled = 0;
$unfulfilled = 0;
$incompatible = 0;

cli_heading('Commerce Fulfillment Audit');

foreach ($reports as $report) {
    if (!$report->is_compatible()) {
        $incompatible++;
        cli_writeln('[FAIL] ' . $report->get_purchase_key()
            . ' issues=' . implode(',', $report->get_issues()));
        continue;
    }

    if ($report->is_fulfilled()) {
        $fulfilled++;
        cli_writeln('[OK] ' . $report->get_purchase_key());
    } else {
        $unfulfilled++;
        cli_writeln('[INFO] ' . $report->get_purchase_key()
            . ' is not currently fulfilled');
    }
}

cli_heading('Summary');
cli_writeln('Processed:    ' . count($reports));
cli_writeln('Fulfilled:    ' . $fulfilled);
cli_writeln('Unfulfilled:  ' . $unfulfilled);
cli_writeln('Incompatible: ' . $incompatible);

if (!empty($options['strict'])
        && ($unfulfilled > 0 || $incompatible > 0)) {
    cli_error('Commerce fulfillment audit failed in strict mode.');
}

exit(0);
