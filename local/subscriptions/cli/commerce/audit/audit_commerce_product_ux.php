<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

[$options] = cli_get_params(['json' => false, 'strict' => false], ['j' => 'json', 's' => 'strict']);
$steps = ['information', 'components', 'pricing', 'preview'];
$errors = [];
foreach ($steps as $step) {
    $label = get_string('commerce_product_step_' . $step, 'local_subscriptions');
    if (preg_match('/^\s*\d+[.)]/', $label)) {
        $errors[] = 'Number embedded in step label: ' . $label;
    }
}
foreach (['course_access', 'digital_download', 'bundle', 'service'] as $type) {
    if (CommerceProductPresentation::type_label($type) === $type) {
        $errors[] = 'Technical type exposed: ' . $type;
    }
}
$report = ['steps' => count($steps), 'types' => 4, 'errors' => $errors, 'certified' => $errors === []];
if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    echo "== Phase 7.94E10 - Commerce CRM UX ==\n";
    echo 'steps:     ' . $report['steps'] . "\n";
    echo 'types:     ' . $report['types'] . "\n";
    echo 'errors:    ' . count($errors) . "\n";
    echo 'certified: ' . ($report['certified'] ? 'yes' : 'no') . "\n";
}
if ($options['strict'] && !$report['certified']) { exit(1); }
