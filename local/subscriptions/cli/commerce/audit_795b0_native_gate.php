<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(
    [
        'baseline' => 'release/commerce-7.94',
        'scope' => 'changed',
        'json' => false,
        'strict' => false,
    ],
    [
        'b' => 'baseline',
        'j' => 'json',
        's' => 'strict',
    ]
);

if (!in_array($options['scope'], ['changed', 'all'], true)) {
    cli_error('--scope must be changed or all.');
}

$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
try {
    $files = $options['scope'] === 'all'
        ? $auditor->all_php_files()
        : $auditor->changed_files((string)$options['baseline']);
} catch (\RuntimeException $exception) {
    cli_error($exception->getMessage());
}

$report = $auditor->native_gate($files);
$report['phase'] = '7.95B0 Native Gate';
$report['scope'] = $options['scope'];
$report['baseline'] = $options['baseline'];

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit($report['passed'] ? 0 : 1);
}

echo "== 7.95 Native Gate ==\n";
echo "Scope: {$options['scope']}\n";
if ($options['scope'] === 'changed') {
    echo "Baseline: {$options['baseline']}\n";
}
echo "Files inspected: " . count($report['files']) . "\n";

$emptychangedscope = $options['scope'] === 'changed' && $report['files'] === [];
if ($emptychangedscope) {
    echo "[WARN] No changed PHP file was detected. Certification only proves that there is currently nothing to inspect.\n";
}

echo "\n";
foreach ($report['checks'] as $id => $check) {
    echo '[' . ($check['passed'] ? 'OK' : 'FAIL') . "] {$id} - {$check['label']}\n";
    foreach ($check['errors'] as $error) {
        echo "      - {$error}\n";
    }
}

$certified = $report['passed'] && !($options['strict'] && $emptychangedscope);
echo "\n" . ($certified ? '[CERTIFIED]' : '[NOT CERTIFIED]') . "\n";
if (!$certified && $options['strict']) {
    cli_error($emptychangedscope
        ? 'Native Gate strict mode cannot certify an empty changed scope.'
        : 'Native Gate failed.');
}
exit($certified ? 0 : 1);
