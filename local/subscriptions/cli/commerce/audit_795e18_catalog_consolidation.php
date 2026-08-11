<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'file_api_options' => !str_contains(file_get_contents($root . '/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php'), 'FILE_INTERNAL'),
    'configured_admin_paths' => str_contains(file_get_contents($root . '/classes/subscription_config.php'), 'commerce_access_scope_edit_page'),
    'provider_hidden_from_catalogue_price_form' => !str_contains(file_get_contents($root . '/admin/commerce/products/prices.php'), "name' => 'provider'"),
    'scope_delete_guard' => is_file($root . '/admin/commerce/accessscopes/delete.php'),
    'plan_delete_guard' => is_file($root . '/admin/commerce/plans/delete.php'),
    'plan_toggle' => is_file($root . '/admin/commerce/plans/toggle.php'),
    'commerce_navigation' => str_contains(file_get_contents($root . '/admin/commerce/plans/index.php'), 'CommerceSectionNavigationRenderer'),
    'safe_cli_config_path' => str_contains(file_get_contents(__FILE__), "require_once(__DIR__ . '/../../../../config.php');"),
];

echo "== 7.95E18 Catalogue consolidation ==\n\n";
$ok = true;
foreach ($checks as $name => $passed) {
    printf("%-48s %s\n", $name, $passed ? 'OK' : 'FAIL');
    $ok = $ok && $passed;
}
echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";
exit($ok ? 0 : 1);
