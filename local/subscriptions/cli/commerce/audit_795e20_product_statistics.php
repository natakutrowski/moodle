<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$root = dirname(__DIR__, 2);
$files = [
    'statistics_page' => $root . '/admin/commerce/statistics/index.php',
    'purchase_list' => $root . '/admin/commerce/purchases/index.php',
    'repository' => $root . '/classes/commerce/statistics/CommerceStatisticsRepository.php',
    'renderer' => $root . '/classes/crm/commerce/statistics/CommerceProductStatisticsRenderer.php',
    'action_service' => $root . '/classes/commerce/purchase/action/CommercePurchaseActionService.php',
    'product_view' => $root . '/admin/commerce/products/view.php',
];
foreach ($files as $name => $path) {
    if (!is_file($path)) {
        cli_error('Missing E20 file: ' . $name . ' (' . $path . ')');
    }
}
$source = array_map('file_get_contents', $files);
$checks = [
    'e20_product_statistics_query_exists' => str_contains($source['repository'], 'function product_statistics('),
    'e20_revenue_requires_successful_payment' => str_contains($source['repository'], "pay.status IN ('paid', 'succeeded', 'completed', 'captured')"),
    'e20_currencies_are_grouped_separately' => str_contains($source['repository'], 'pi.currency'),
    'e20_product_table_is_rendered' => str_contains($source['statistics_page'], 'CommerceProductStatisticsRenderer::render($productstatistics)'),
    'e20_closed_purchases_are_loaded_in_batch' => str_contains($source['action_service'], 'closed_without_fulfillment_ids(array $purchaseids)'),
    'e20_purchase_list_hides_retry_after_close' => str_contains($source['purchase_list'], '!isset($closedwithoutfulfillment[$purchase->id])'),
    'e20_product_types_are_translated' => str_contains($source['renderer'], 'self::sale_type($row->itemtype)'),
    'e20_product_view_has_metrics_and_charts' => str_contains($source['product_view'], '$statisticsrepository->product_statistics(')
        && str_contains($source['product_view'], 'CommerceStatisticsChartRenderer::product('),
    'e20_product_translations_use_flags_and_html' => str_contains($source['product_view'], "'fr' => '🇫🇷'")
        && str_contains($source['product_view'], "format_text(\$translation['description'], FORMAT_HTML)"),
];
cli_writeln('== 7.95E20 Product statistics ==');
cli_writeln('');
$ok = true;
foreach ($checks as $label => $passed) {
    cli_writeln(str_pad($label, 58) . ($passed ? 'OK' : 'FAILED'));
    $ok = $ok && $passed;
}
cli_writeln('');
cli_writeln($ok ? '[CERTIFIED]' : '[FAILED]');
exit($ok ? 0 : 1);
