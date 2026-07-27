<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchCriteria;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchService;

[$options] = cli_get_params([
    'purchase' => null,
    'source' => null,
    'entrypoint' => null,
    'status' => null,
    'classification' => null,
    'userid' => 0,
    'family' => null,
    'limit' => 50,
    'offset' => 0,
    'json' => false,
], ['j' => 'json']);

$criteria = new CommerceShadowSearchCriteria(
    $options['purchase'],
    $options['source'],
    $options['entrypoint'],
    $options['status'],
    $options['classification'],
    (int) $options['userid'] > 0 ? (int) $options['userid'] : null,
    $options['family'],
    (int) $options['limit'],
    (int) $options['offset']
);
$rows = (new CommerceShadowSearchService())->search($criteria);
if ($options['json']) {
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

echo "== Commerce Shadow Search ==\n\n";
foreach ($rows as $row) {
    printf(
        "#%d %s | %s | %s | %s\n",
        $row['id'],
        $row['purchasereference'],
        $row['classification'],
        $row['source'],
        userdate($row['timecreated'])
    );
    if ($row['differences'] !== []) {
        echo '  differences: ' . json_encode($row['differences'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    if ($row['errormessage'] !== null && $row['errormessage'] !== '') {
        echo '  error: ' . $row['errormessage'] . PHP_EOL;
    }
}
echo PHP_EOL . 'results: ' . count($rows) . PHP_EOL;
