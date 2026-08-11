<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$audits = [
    'C1-C3' => 'cli/commerce/audit_795c1_c3_statistics.php',
    'C4-C6' => 'cli/commerce/audit_795c4_c6_dashboard.php',
    'C7-C9' => 'cli/commerce/audit_795c7_c9_graphics.php',
    'C10-C11' => 'cli/commerce/audit_795c10_c11_certification.php',
];

$ok = true;
echo "== 7.95C Commerce statistics complete block ==\n\n";
foreach ($audits as $label => $relativepath) {
    $path = $root . '/' . $relativepath;
    $valid = is_file($path);
    printf("%-12s %s\n", $label, $valid ? 'PRESENT' : 'MISSING');
    $ok = $ok && $valid;
}

echo "\nThis aggregator verifies that every certification entrypoint is installed.\n";
echo "Run each audit shown above for its detailed runtime certification.\n\n";
echo ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";

exit($ok ? 0 : 1);
