<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$checks = [];
$read = static fn(string $path): string => (string)file_get_contents($root . '/' . $path);
$view = $read('admin/commerce/products/view.php');
$purchases = $read('admin/commerce/purchases/index.php');
$navigation = $read('classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php');
$repository = $read('classes/commerce/statistics/CommerceStatisticsRepository.php');

$checks['e21_product_statistics_support_legacy_references'] =
    str_contains($view, "'subscription-plan:'") &&
    str_contains($view, "'digital-product:'") &&
    str_contains($repository, 'product_statistics_for_references');
$checks['e21_product_revenue_series_supports_all_references'] =
    str_contains($repository, 'product_revenue_series_for_references');
$checks['e21_digital_files_have_admin_download_links'] =
    str_contains($view, 'products/download.php') &&
    is_file($root . '/admin/commerce/products/download.php');
$checks['e21_digital_files_do_not_duplicate_cover'] =
    !str_contains($view, '$digital->coverimage');
$checks['e21_legacy_digital_navigation_removed'] =
    !str_contains($navigation, 'subscription_config::digital_products_admin_page()');
$checks['e21_purchase_products_are_linked'] =
    str_contains($purchases, 'products/view.php') && str_contains($purchases, '$purchase->productitems');
$checks['e21_purchase_list_uses_one_business_status'] =
    !str_contains($purchases, "technical_status_badge('payment'") &&
    !str_contains($purchases, "technical_status_badge('fulfillment'");
$checks['e21_price_label_is_capitalised'] =
    str_contains($read('lang/fr/local_subscriptions.php'), "$" . "string['commerce_prices'] = 'Prix';");

echo "== 7.95E21 CRM consolidation ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    echo str_pad($name, 62) . ($ok ? 'OK' : 'FAILED') . "\n";
    $failed = $failed || !$ok;
}
echo "\n[" . ($failed ? 'FAILED' : 'CERTIFIED') . "]\n";
exit($failed ? 1 : 0);
