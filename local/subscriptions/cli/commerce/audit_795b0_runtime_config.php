<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options] = cli_get_params(
    ['json' => false, 'strict' => false],
    ['j' => 'json', 's' => 'strict']
);

$expected = [
    'commerce_runtime_mode' => 'native',
    'commerce_runtime_read_mode' => 'native',
    'commerce_runtime_read_strict' => '1',
    'commerce_runtime_native_fallback_enabled' => '1',
    'commerce_fulfillment_shadow_enabled' => '0',
];

$report = [];
$passed = true;
foreach ($expected as $key => $expectedvalue) {
    $rawvalue = get_config('local_subscriptions', $key);
    $actualvalue = $rawvalue === false ? '<unset>' : (string)$rawvalue;
    $ok = $actualvalue === $expectedvalue;
    $passed = $passed && $ok;
    $report[] = [
        'key' => $key,
        'actual' => $actualvalue,
        'expected' => $expectedvalue,
        'passed' => $ok,
    ];
}

if ($options['json']) {
    echo json_encode(
        ['phase' => '7.95B0.8', 'passed' => $passed, 'settings' => $report],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n";
    exit($passed ? 0 : 1);
}

echo "== 7.95B0.8 Native runtime configuration ==\n";
foreach ($report as $item) {
    echo sprintf(
        "[%s] %-48s actual=%-10s expected=%s\n",
        $item['passed'] ? 'OK' : 'FAIL',
        $item['key'],
        $item['actual'],
        $item['expected']
    );
}

echo "\n" . ($passed ? '[CERTIFIED]' : '[NOT CERTIFIED]') . "\n";
if (!$passed && $options['strict']) {
    cli_error('Native runtime configuration does not match the 7.95 DEV target.');
}
exit($passed ? 0 : 1);
