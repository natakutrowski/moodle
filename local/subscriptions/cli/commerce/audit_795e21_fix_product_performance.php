<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = dirname(__DIR__, 2);
$read = static fn(string $path): string => (string)file_get_contents($root . '/' . $path);
$view = $read('admin/commerce/products/view.php');
$purchases = $read('admin/commerce/purchases/index.php');
$catalogue = $read('classes/commerce/catalog/readmodel/CommerceCatalogReadRepository.php');
$statistics = $read('classes/commerce/statistics/CommerceStatisticsRepository.php');

$checks = [
    'e21fix_purchase_links_resolve_catalogue_identity' =>
        str_contains($purchases, 'find_by_purchase_reference') &&
        str_contains($purchases, 'CommerceCatalogLinkGenerator::view_url'),
    'e21fix_digital_statistics_include_slug_reference' =>
        str_contains($view, "'digital-product:' . trim") &&
        str_contains($catalogue, "preg_match('/^digital-product:(.+)$/i'"),
    'e21fix_legacy_digital_cover_has_priority' =>
        str_contains($view, '$legacydigital && !empty($legacydigital->coverimage)') &&
        str_contains($view, '$coverurl === null && $product->get_origin()'),
    'e21fix_product_statistics_filter_currency' =>
        str_contains($view, "optional_param('statscurrency'") &&
        str_contains($statistics, 'pi.currency = :productcurrency'),
    'e21fix_product_statistics_filter_period' =>
        str_contains($view, "optional_param('statsperiod'") &&
        str_contains($view, 'CommerceStatisticsPeriod::custom(0, time() + 1)'),
    'e21fix_all_currencies_are_rendered_in_separate_blocks' =>
        str_contains($view, "html_writer::start_tag('section'") &&
        str_contains($view, "html_writer::tag('h4', s(\$currency)"),
];

echo "== 7.95E21 fix — product performance ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    echo str_pad($name, 68) . ($ok ? 'OK' : 'FAILED') . "\n";
    $failed = $failed || !$ok;
}
echo "\n[" . ($failed ? 'FAILED' : 'CERTIFIED') . "]\n";
exit($failed ? 1 : 0);
