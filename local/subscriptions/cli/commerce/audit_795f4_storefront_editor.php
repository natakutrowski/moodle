<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f4_storefront_editor_controller_available' => is_file($root . '/admin/commerce/products/storefront.php'),
    'f4_storefront_editor_service_available' => is_file($root . '/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'),
    'f4_product_navigation_exposes_storefront_step' => str_contains((string)file_get_contents($root . '/classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php'), "public const STOREFRONT"),
    'f4_editor_preserves_commercial_boundary' => !str_contains((string)file_get_contents($root . '/admin/commerce/products/storefront.php'), 'price'),
    'f4_editor_preserves_other_metadata' => str_contains((string)file_get_contents($root . '/classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php'), "\$metadata['storefront']"),
    'f4_components_are_resolved_from_live_catalogue' => str_contains((string)file_get_contents($root . '/classes/commerce/storefront/page/CommerceStorefrontPageResolver.php'), "\$product->get_components()"),
];

echo "== 7.95F4 Storefront CRM editor ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-68s %s\n", $name, $ok ? 'OK' : 'FAILED');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
