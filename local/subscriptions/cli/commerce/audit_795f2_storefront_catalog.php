<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f2_unified_storefront_uses_read_model' => str_contains((string)file_get_contents($root . '/digital_catalog.php'), 'CommerceStorefrontRepository'),
    'f2_storefront_has_mustache_catalogue' => is_file($root . '/templates/storefront/catalog.mustache'),
    'f2_storefront_has_product_cards' => is_file($root . '/templates/storefront/product_card.mustache'),
    'f2_storefront_has_quick_purchase_and_discover' => str_contains((string)file_get_contents($root . '/templates/storefront/product_card.mustache'), 'quickpurchaseurl')
        && str_contains((string)file_get_contents($root . '/templates/storefront/product_card.mustache'), 'detailsurl'),
    'f2_storefront_supports_cover_images' => str_contains((string)file_get_contents($root . '/classes/commerce/storefront/repository/CommerceStorefrontRepository.php'), 'cover_url'),
    'f2_storefront_presentation_is_externalised' => is_file($root . '/styles/storefront.css')
        && !str_contains((string)file_get_contents($root . '/digital_catalog.php'), '<style'),
    'f2_legacy_checkout_is_resolved_outside_templates' => is_file($root . '/classes/commerce/storefront/presentation/CommerceStorefrontUrlResolver.php'),
];

echo "== 7.95F2 Unified Storefront catalogue ==\n\n";
$failed = false;
foreach ($checks as $label => $ok) {
    echo str_pad($label, 66) . ($ok ? 'OK' : 'FAILED') . "\n";
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
