<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceStorefrontUxCertificationAuditor;

[$options, $unrecognized] = cli_get_params(['json' => false, 'help' => false], ['h' => 'help']);
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "Commerce 7.95F7F storefront UX source certification\n\nOptions: --json\n";
    exit(0);
}

$report = (new CommerceStorefrontUxCertificationAuditor(__DIR__ . '/../..'))->audit()->to_array();
if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($report['certifiable'] ? 0 : 1);
}

echo "# 7.95F7F — Storefront UX certification\n\n";
echo '- Certifiable: ' . ($report['certifiable'] ? 'YES' : 'NO') . PHP_EOL;
echo '- Blocking issues: ' . $report['summary']['blocking'] . PHP_EOL;
echo '- Important issues: ' . $report['summary']['important'] . PHP_EOL;
echo '- Cosmetic issues: ' . $report['summary']['cosmetic'] . PHP_EOL;
echo "\n## Checks\n\n";
foreach ($report['inventory'] as $key => $value) {
    echo '- **' . $key . '**: ' . (is_bool($value) ? ($value ? 'PASS' : 'FAIL') : $value) . PHP_EOL;
}
echo "\n## Issues\n\n";
if (!$report['issues']) {
    echo "No issue detected.\n";
} else {
    foreach ($report['issues'] as $issue) {
        echo '- **' . strtoupper($issue['severity']) . ' / ' . $issue['code'] . '** — ' . $issue['message'] . PHP_EOL;
    }
}
exit($report['certifiable'] ? 0 : 1);
