<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(
    ['json' => false, 'strict' => false, 'verbose' => false],
    ['j' => 'json', 's' => 'strict', 'v' => 'verbose']
);

$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
$accesses = $auditor->audit_data_access();

if ($options['json']) {
    echo json_encode(
        ['phase' => '7.95B0.5', 'accesses' => $accesses],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n";
    exit(0);
}

echo "== 7.95B0.5 Direct database access in UI ==\n";
$totalcalls = 0;
$commercecalls = 0;
$dynamiccalls = 0;
$blockingfiles = 0;

foreach ($accesses as $access) {
    $totalcalls += count($access['calls']);
    $commercecalls += count($access['commercecalls']);
    $dynamiccalls += count($access['dynamiccalls']);

    if (in_array($access['status'], ['commerce-db-access', 'dynamic-review'], true)) {
        $blockingfiles++;
    }

    echo sprintf(
        "[%s] %-75s calls=%d commerce=%d dynamic=%d\n",
        strtoupper($access['status']),
        $access['file'],
        count($access['calls']),
        count($access['commercecalls']),
        count($access['dynamiccalls'])
    );

    if ($options['verbose']) {
        foreach ($access['calls'] as $call) {
            echo "      L{$call['line']} \$DB->{$call['method']} table="
                . ($call['table'] ?: '<dynamic>')
                . " classification={$call['classification']}\n";
        }
    }
}

echo "\nUI files with DB calls: " . count($accesses) . "\n";
echo "Blocking UI files: {$blockingfiles}\n";
echo "Direct DB calls: {$totalcalls}\n";
echo "Commerce DB calls: {$commercecalls}\n";
echo "Dynamic DB calls requiring review: {$dynamiccalls}\n";

if ($blockingfiles > 0 && $options['strict']) {
    cli_error('Direct Commerce or unresolved dynamic database access remains in audited UI files.');
}

echo $blockingfiles === 0 ? "[OK]\n" : "[WARN]\n";
