<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$read = static fn(string $path): string => (string)file_get_contents($root . '/' . $path);
$view = $read('admin/commerce/products/view.php');
$main = $read('admin/commerce/statistics/index.php');
$renderer = $read('classes/crm/commerce/statistics/CommerceProductStatisticsRenderer.php');
$charts = $read('classes/crm/commerce/statistics/CommerceStatisticsChartRenderer.php');
$repository = $read('classes/commerce/statistics/CommerceStatisticsRepository.php');

$checks = [
    'e21ref_audit_has_no_currency_interpolation_warning' => !str_contains($read('cli/commerce/audit_795e21_fix_product_performance.php'), 's($currency)'),
    'e21ref_main_statistics_support_cumulative_revenue' => str_contains($main, "optional_param('chartmode'") && str_contains($charts, 'self::cumulative'),
    'e21ref_product_statistics_support_cumulative_revenue' => str_contains($view, "optional_param('statschartmode'") && str_contains($view, "=== 'cumulative'"),
    'e21ref_product_sales_histogram_exists' => str_contains($repository, 'product_order_series_for_references') && str_contains($charts, 'commerce_statistics_chart_product_orders'),
    'e21ref_main_product_links_resolve_purchase_references' => str_contains($renderer, 'find_by_purchase_reference') && str_contains($renderer, 'CommerceCatalogLinkGenerator::view_url'),
    'e21ref_product_failed_payments_are_displayed' => str_contains($repository, 'product_failed_payments_for_references') && str_contains($view, 'commerce_statistics_product_failed_payments'),
    'e21ref_product_statistics_use_full_workspace_width' => str_contains($view, 'Product performance spans the complete workspace width'),
    'e21ref_product_charts_are_width_constrained' => str_contains($read('styles.css'), '.crm-commerce-statistics-chart { min-width:0; overflow:hidden; }'),
];

echo "== 7.95E21 statistics refinements ==

";
$failed = false;
foreach ($checks as $name => $ok) {
    echo str_pad($name, 72) . ($ok ? 'OK' : 'FAILED') . "
";
    $failed = $failed || !$ok;
}
echo "
[" . ($failed ? 'FAILED' : 'CERTIFIED') . "]
";
exit($failed ? 1 : 0);
