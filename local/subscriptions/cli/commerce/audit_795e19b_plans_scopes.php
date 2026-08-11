<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'cover_errors_notified' => str_contains(file_get_contents($root . '/admin/commerce/products/assets.php'), 'NOTIFY_ERROR'),
    'product_view_return_link' => str_contains(file_get_contents($root . '/classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php'), 'commerce_product_back_to_view'),
    'shared_plan_toggle' => is_file($root . '/classes/commerce/catalog/presentation/CommercePlanStatusToggleRenderer.php'),
    'plan_toggle_post_only' => str_contains(file_get_contents($root . '/admin/commerce/plans/toggle.php'), "REQUEST_METHOD'] !== 'POST'"),
    'plan_view_technical_dates' => str_contains(file_get_contents($root . '/admin/commerce/plans/view.php'), 'commerce_date_created'),
    'scope_view_technical_dates' => str_contains(file_get_contents($root . '/admin/commerce/accessscopes/view.php'), 'commerce_date_created'),
];

echo "== 7.95E19B Plans and Access Scopes ==\n\n";
$failed = false;
foreach ($checks as $name => $passed) {
    printf("%-42s %s\n", $name, $passed ? 'OK' : 'FAIL');
    $failed = $failed || !$passed;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
