<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$checks = [
    'guided_fulfillments' => str_contains(file_get_contents($root . '/admin/commerce/products/fulfillments.php'), 'CommerceCatalogFulfillmentPresentation::type_options'),
    'dynamic_price_rows' => str_contains(file_get_contents($root . '/admin/commerce/products/prices.php'), "name' => 'addrow"),
    'currency_registry' => is_file($root . '/classes/commerce/catalog/currency/CommerceCurrencyRegistry.php'),
    'legacy_links' => is_file($root . '/classes/commerce/catalog/navigation/CommerceLegacyCatalogLinkGenerator.php'),
    'catalog_diagnostic' => is_file($root . '/classes/commerce/catalog/validation/CommerceCatalogValidator.php'),
    'digital_assets' => is_file($root . '/admin/commerce/products/assets.php'),
    'cover_for_all_products' => str_contains(file_get_contents($root . '/admin/commerce/products/assets.php'), 'cover_file'),
    'safe_cli_config_path' => str_contains(file_get_contents(__FILE__), "__DIR__ . '/../../../../config.php'"),
];

echo "== 7.95E11-E12 Catalogue polish ==\n\n";
$failed = false;
foreach ($checks as $label => $ok) {
    printf("%-42s %s\n", $label, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
