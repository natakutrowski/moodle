<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\shadow\reporting\CommerceShadowReportExporter;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchCriteria;
use local_subscriptions\commerce\shadow\reporting\CommerceShadowSearchService;

[$options] = cli_get_params([
    'format' => 'json',
    'output' => null,
    'purchase' => null,
    'classification' => null,
    'limit' => 1000,
], []);
$format = strtolower((string) $options['format']);
if (!in_array($format, ['json', 'csv'], true)) {
    throw new \coding_exception('Commerce Shadow export format must be json or csv.');
}
$rows = (new CommerceShadowSearchService())->search(new CommerceShadowSearchCriteria(
    $options['purchase'],
    null,
    null,
    null,
    $options['classification'],
    null,
    null,
    (int) $options['limit']
));
$exporter = new CommerceShadowReportExporter();
$content = $format === 'csv' ? $exporter->export_csv($rows) : $exporter->export_json($rows);
if ($options['output'] !== null && trim((string) $options['output']) !== '') {
    $written = file_put_contents((string) $options['output'], $content);
    if ($written === false) {
        throw new \RuntimeException('Unable to write Commerce Shadow export file.');
    }
    echo 'Export written: ' . $options['output'] . PHP_EOL;
    exit(0);
}
echo $content;
