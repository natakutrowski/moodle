<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

if (PHP_SAPI !== 'cli') {
    throw new coding_exception('CLI only.');
}

$audits = [
    'D1-D3' => __DIR__ . '/audit_795d1_d3_purchase_contract.php',
    'D4-D6' => __DIR__ . '/audit_795d4_d6_unified_ui.php',
    'D7-D10' => __DIR__ . '/audit_795d7_d10_actions_compatibility.php',
    'D11-D12' => __DIR__ . '/audit_795d11_d12_certification.php',
    'D13' => __DIR__ . '/audit_795d13_completion.php',
];

mtrace('== 7.95D Unified Commerce sales complete block ==');
mtrace('');
$failed = false;
foreach ($audits as $label => $path) {
    $present = is_file($path);
    mtrace(str_pad($label, 13) . ($present ? 'PRESENT' : 'MISSING'));
    $failed = $failed || !$present;
}
mtrace('');
mtrace('This aggregator verifies that every certification entrypoint is installed.');
mtrace('Run each detailed audit separately for runtime certification.');
mtrace('');
mtrace($failed ? '[FAILED]' : '[CERTIFIED]');
exit($failed ? 1 : 0);
