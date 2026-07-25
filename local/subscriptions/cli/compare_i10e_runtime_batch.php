<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\reconciliation\CommerceReconciliationFactory;

[$options] = cli_get_params(
    [
        'family' => null,
        'ids' => null,
        'strict' => false,
    ],
    [
        'h' => 'help',
    ]
);

$family = strtolower(trim((string) $options['family']));

$ids = array_values(
    array_filter(
        array_map(
            'intval',
            preg_split('/[,\s]+/', (string) $options['ids'])
        )
    )
);

if (
    !in_array($family, ['digital', 'subscription'], true)
    || $ids === []
) {
    cli_error(
        'Use --family=digital|subscription --ids=1,2,3'
    );
}

$service = CommerceReconciliationFactory::create();
$issues = 0;

cli_writeln('== I10E runtime batch comparison ==');

foreach ($ids as $id) {
    $result = $service->reconcile(
        $family,
        $id,
        false
    );

    $count = count($result->get_issues());
    $issues += $count;

    cli_writeln(
        sprintf(
            '  %-12s #%d equal=%s issues=%d repaired=%s',
            $family,
            $id,
            $result->is_equal() ? 'yes' : 'no',
            $count,
            $result->was_repaired() ? 'yes' : 'no'
        )
    );
}

if (
    !empty($options['strict'])
    && $issues > 0
) {
    cli_error(
        'Runtime batch comparison found differences.'
    );
}

cli_writeln(
    '[OK] Compared '
    . count($ids)
    . ' Legacy/Native purchase(s); issues='
    . $issues
    . '.'
);