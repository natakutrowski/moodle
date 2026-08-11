<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6h_origin_beside_sku' => str_contains(file_get_contents($root . '/admin/commerce/products/index.php'), 'commerce_catalog_origin_legacy_short'),
    'f6h_origin_filter' => str_contains(file_get_contents($root . '/admin/commerce/products/index.php'), "optional_param('origin'"),
    'f6h_shared_badge_partial' => is_file($root . '/templates/storefront/product_badges.mustache'),
    'f6h_product_page_badges' => str_contains(file_get_contents($root . '/templates/storefront/product_templates/default.mustache'), 'storefront/product_badges'),
    'f6h_gustave_xl' => str_contains(file_get_contents($root . '/styles/storefront.css'), 'width: 4.25rem;'),
    'f6h_f6g_test_interpolation_fix' => str_contains(file_get_contents($root . '/tests/commerce/storefront/commerce_795f6g_final_polish_test.php'), "<<<'PHP'"),
];

echo "== 7.95F6H Legacy visibility and shared badges ==\n\n";
$ok = true;
foreach ($checks as $name => $passed) {
    printf("%-50s %s\n", $name, $passed ? 'OK' : 'FAIL');
    $ok = $ok && $passed;
}
echo "\n" . ($ok ? '[CERTIFIED]' : '[NOT CERTIFIED]') . "\n";
exit($ok ? 0 : 1);
