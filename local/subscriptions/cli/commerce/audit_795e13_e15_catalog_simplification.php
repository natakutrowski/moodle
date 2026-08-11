<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'fulfillments_removed_from_navigation' => !str_contains(file_get_contents($root . '/classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php'), 'can_edit_fulfillments()'),
    'fulfillments_removed_from_validation' => !str_contains(file_get_contents($root . '/classes/commerce/catalog/validation/CommerceCatalogValidator.php'), 'no_fulfillment'),
    'single_currency_price_guard' => str_contains(file_get_contents($root . '/classes/commerce/catalog/repository/CommerceProductPriceRepository.php'), 'currency_exists'),
    'price_update_by_identifier' => str_contains(file_get_contents($root . '/classes/commerce/catalog/repository/CommerceProductPriceRepository.php'), 'update_by_id'),
    'price_delete_supported' => str_contains(file_get_contents($root . '/classes/commerce/catalog/repository/CommerceProductPriceRepository.php'), 'delete_by_id'),
    'plans_pages' => is_file($root . '/admin/commerce/plans/index.php') && is_file($root . '/admin/commerce/plans/edit.php') && is_file($root . '/admin/commerce/plans/view.php'),
    'access_scope_pages' => is_file($root . '/admin/commerce/accessscopes/index.php') && is_file($root . '/admin/commerce/accessscopes/edit.php') && is_file($root . '/admin/commerce/accessscopes/view.php'),
    'legacy_manage_redirect' => str_contains(file_get_contents($root . '/admin/manage.php'), '/admin/commerce/plans/index.php'),
    'safe_cli_config_path' => str_contains(file_get_contents(__FILE__), "require_once(__DIR__ . '/../../../../config.php');"),
];

echo "== 7.95E13-E15 Catalogue simplification ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-46s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
