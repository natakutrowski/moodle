<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'plan_view_entitlements' => [
        'file' => '/admin/commerce/plans/view.php',
        'needles' => ['subscription_plan_entitlement', 'plan_entitlements_page()'],
    ],
    'plan_view_upgrades' => [
        'file' => '/admin/commerce/plans/view.php',
        'needles' => ['subscription_plan_upgrade', 'plan_upgrades_page()'],
    ],
    'entitlements_commerce_navigation' => [
        'file' => '/admin/plans/entitlements.php',
        'needles' => ['CommerceSectionNavigationRenderer::render', 'commerce_plan_view_page()'],
    ],
    'upgrades_plan_filter' => [
        'file' => '/admin/plans/upgrades.php',
        'needles' => ["optional_param('planid'", 'u.fromplanid = :fromplanid OR u.toplanid = :toplanid'],
    ],
    'upgrades_prefill_source_plan' => [
        'file' => '/admin/plans/upgrades.php',
        'needles' => ['$defaults->fromplanid = $planid'],
    ],
];

$failed = false;
echo "== 7.95E19C Plan entitlements and upgrades ==\n\n";
foreach ($checks as $name => $check) {
    $path = $root . $check['file'];
    $content = is_file($path) ? file_get_contents($path) : '';
    $ok = $content !== '';
    foreach ($check['needles'] as $needle) {
        $ok = $ok && str_contains($content, $needle);
    }
    printf("%-42s %s\n", $name, $ok ? 'OK' : 'FAILED');
    $failed = $failed || !$ok;
}

echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
