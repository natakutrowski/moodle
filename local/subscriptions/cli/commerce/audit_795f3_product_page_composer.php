<?php


define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f3_page_definition_available' => is_file($root . '/classes/commerce/storefront/page/CommerceStorefrontPageDefinition.php'),
    'f3_safe_page_resolver_available' => is_file($root . '/classes/commerce/storefront/page/CommerceStorefrontPageResolver.php'),
    'f3_page_presenter_available' => is_file($root . '/classes/commerce/storefront/page/CommerceStorefrontPagePresenter.php'),
    'f3_default_template_available' => is_file($root . '/templates/storefront/product_templates/default.mustache'),
    'f3_editorial_template_available' => is_file($root . '/templates/storefront/product_templates/editorial.mustache'),
    'f3_immersive_template_available' => is_file($root . '/templates/storefront/product_templates/immersive.mustache'),
    'f3_common_commerce_panel_preserved' => is_file($root . '/templates/storefront/product_commerce_panel.mustache'),
    'f3_structured_sections_available' => is_file($root . '/templates/storefront/product_sections.mustache'),
];

$controller = file_get_contents($root . '/storefront_product.php');
$checks['f3_controller_uses_composition_engine'] = str_contains($controller, 'CommerceStorefrontPageResolver')
    && str_contains($controller, 'get_template()');

fwrite(STDOUT, "== 7.95F3 Product page composer ==\n\n");
$failed = false;
foreach ($checks as $name => $ok) {
    fwrite(STDOUT, str_pad($name, 68) . ($ok ? "OK\n" : "FAILED\n"));
    $failed = $failed || !$ok;
}

fwrite(STDOUT, $failed ? "\n[FAILED]\n" : "\n[CERTIFIED]\n");
exit($failed ? 1 : 0);
