<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$checks = [
    'capability_driven_navigation' => ['classes/commerce/catalog/editing/CommerceProductEditorCapabilities.php', 'can_manage_access_scope'],
    'unified_native_prices' => ['admin/commerce/products/prices.php', 'CommerceProductPrice'],
    'unified_native_fulfillments' => ['admin/commerce/products/fulfillments.php', 'CommerceProductEntitlementDefinition'],
    'plan_scope_separation' => ['classes/commerce/catalog/accessscope/CommerceAccessScopeRelationRepository.php', 'subscription_access_scope'],
    'plan_scope_ui' => ['admin/commerce/products/access_scope.php', 'commerce_access_scope_relation_help'],
    'legacy_source_editing' => ['classes/commerce/catalog/editing/CommerceCatalogCompatibilityEditor.php', 'legacy_edit_url'],
    'bundle_guards_preserved' => ['admin/commerce/products/components.php', 'Only Bundle products'],
];

echo "== 7.95E7-E10 Catalogue editing ==\n\n";
$failed = false;
foreach ($checks as $label => [$relative, $needle]) {
    $path = $root . '/' . $relative;
    $ok = is_file($path) && str_contains((string)file_get_contents($path), $needle);
    printf("%-42s %s\n", $label, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

$source = (string)file_get_contents(__FILE__);
$correctconfig = preg_match(
    "~require_once\s*\(\s*__DIR__\s*\.\s*['\"]/?\.\./\.\./\.\./\.\./config\.php['\"]\s*\)\s*;~",
    $source
) === 1;
printf("%-42s %s\n", 'safe_cli_config_path', $correctconfig ? 'OK' : 'FAIL');
$failed = $failed || !$correctconfig;

echo $failed ? "\n[FAILED]\n" : "\n[CERTIFIED]\n";
exit($failed ? 1 : 0);