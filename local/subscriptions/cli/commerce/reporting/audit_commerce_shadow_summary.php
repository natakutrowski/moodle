<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\reporting\CommerceShadowStatisticsService;

[$options] = cli_get_params(
    ['json' => false, 'from' => 0, 'to' => 0],
    ['j' => 'json']
);
$summary = (new CommerceShadowStatisticsService())->summarize(
    (int) $options['from'] ?: null,
    (int) $options['to'] ?: null
);
$data = $summary->to_array();
if ($options['json']) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "== Commerce Shadow Summary ==\n\n";
printf("%-24s %d\n", 'executions:', $data['total']);
foreach ($data['byclassification'] as $name => $count) {
    printf("%-24s %d\n", $name . ':', $count);
}
printf("%-24s %d ms\n", 'average_duration:', $data['averagedurationms']);
