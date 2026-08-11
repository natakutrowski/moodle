<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\crm\commerce\navigation\CommerceSectionNavigationRegistry;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;

$checks = [];
$items = (new CommerceSectionNavigationRegistry())->all_items();
$keys = array_map(static fn($item): string => $item->key, $items);
$positions = array_map(static fn($item): int => $item->position, $items);
$checks['navigation_registry'] = count($items) === 7
    && count($keys) === count(array_unique($keys))
    && count($positions) === count(array_unique($positions));
$checks['design_system'] = class_exists(CommerceDesignSystemRenderer::class);
$checks['product_header'] = class_exists(CommerceProductPageHeaderRenderer::class);
$checks['product_index_migrated'] = str_contains(
    (string) file_get_contents(__DIR__ . '/../../admin/commerce/products/index.php'),
    'CommerceDesignSystemRenderer'
);
$checks['product_view_migrated'] = str_contains(
    (string) file_get_contents(__DIR__ . '/../../admin/commerce/products/view.php'),
    'CommerceProductPageHeaderRenderer'
);

$passed = !in_array(false, $checks, true);
echo "== 7.95B2-B4 Commerce UX foundation ==\n\n";
foreach ($checks as $name => $ok) {
    echo str_pad($name, 28) . ($ok ? 'OK' : 'FAIL') . "\n";
}
echo "\n" . ($passed ? '[CERTIFIED]' : '[FAILED]') . "\n";
exit($passed ? 0 : 1);
