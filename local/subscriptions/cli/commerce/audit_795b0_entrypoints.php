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
$entrypoints = $auditor->audit_entrypoints();

if ($options['json']) {
    echo json_encode(
        ['phase' => '7.95B0.3', 'entrypoints' => $entrypoints],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ) . "\n";
    exit(0);
}

echo "== 7.95B0.3 Native UI entrypoints ==\n";
$blocking = 0;
$counts = ['native' => 0, 'legacy' => 0, 'mixed' => 0, 'unclassified' => 0];

foreach ($entrypoints as $entrypoint) {
    $status = $entrypoint['status'];
    $counts[$status]++;
    if (in_array($status, ['legacy', 'mixed'], true)) {
        $blocking++;
    }

    echo sprintf(
        "[%s] %-70s native=%d legacy=%d db=%d\n",
        strtoupper($status),
        $entrypoint['file'],
        count($entrypoint['nativeuses']),
        count($entrypoint['legacyfindings']),
        count($entrypoint['directdbcalls'])
    );

    if ($options['verbose']) {
        foreach ($entrypoint['legacyfindings'] as $finding) {
            echo "      L{$finding['line']} {$finding['label']}: {$finding['match']}\n";
        }
    }
}

echo "\nEntrypoints: " . count($entrypoints) . "\n";
echo "Native: {$counts['native']}\n";
echo "Legacy: {$counts['legacy']}\n";
echo "Mixed: {$counts['mixed']}\n";
echo "Unclassified: {$counts['unclassified']}\n";

if ($blocking > 0 && $options['strict']) {
    cli_error('UI entrypoint audit found direct Legacy dependencies.');
}

echo $blocking === 0 ? "[OK]\n" : "[WARN]\n";
