<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\storefront\audit\CommerceStorefrontBaselineAuditor;

$pluginroot = $CFG->dirroot . '/local/subscriptions';
$report = (new CommerceStorefrontBaselineAuditor())->audit($pluginroot);

echo "== 7.95F0 Storefront baseline ==\n\n";
echo 'Public Commerce pages: ' . $report['publicfilecount'] . "\n";
echo 'Approximate lines audited: ' . $report['totallines'] . "\n";
echo 'General Mustache templates: ' . $report['templatecount'] . "\n";
echo 'Storefront Mustache templates: ' . $report['storefronttemplatecount'] . "\n\n";

echo "Legacy table dependencies by page:\n";
if ($report['legacyreferences'] === []) {
    echo "  none\n";
} else {
    foreach ($report['legacyreferences'] as $file => $tables) {
        echo '  - ' . $file . ': ' . implode(', ', array_unique($tables)) . "\n";
    }
}

echo "\nPages containing inline presentation rules:\n";
if ($report['inlinecssfiles'] === []) {
    echo "  none\n";
} else {
    foreach ($report['inlinecssfiles'] as $file) {
        echo '  - ' . $file . "\n";
    }
}

$checks = [
    'f0_public_surface_inventory_completed' => $report['publicfilecount'] >= 6,
    'f0_legacy_dependencies_identified' => $report['legacyreferences'] !== [],
    'f0_unified_catalogue_available' => $report['hasunifiedcatalogue'],
    'f1_storefront_read_boundary_available' => $report['hasstorefrontreadmodel'],
];

echo "\nChecks:\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-62s %s\n", $name, $ok ? 'OK' : 'FAILED');
    $failed = $failed || !$ok;
}

echo $failed ? "\n[FAILED]\n" : "\n[CERTIFIED]\n";
exit($failed ? 1 : 0);
