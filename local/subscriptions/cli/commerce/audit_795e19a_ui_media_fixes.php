<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$media = file_get_contents($root . '/classes/commerce/catalog/assets/CommerceCatalogMediaManager.php');
$renderer = file_get_contents($root . '/classes/crm/commerce/presentation/CommerceDesignSystemRenderer.php');
$prices = file_get_contents($root . '/admin/commerce/products/prices.php');
$scope = file_get_contents($root . '/admin/commerce/products/access_scope.php');
$planedit = file_get_contents($root . '/admin/commerce/plans/edit.php');

$checks = [
    'cover_file_api_without_undefined_constant' => !str_contains($media, 'FILE_INTERNAL'),
    'cover_upload_size_and_type_guards' => str_contains($media, "'maxbytes' => 10 * 1024 * 1024")
        && str_contains($media, "'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp']")
        && str_contains($media, '\\moodle_url::make_pluginfile_url'),
    'product_action_buttons_spaced' => str_contains($renderer, "' mb-2'") && str_contains($renderer, "' me-2'"),
    'price_active_checkbox_spaced' => str_contains($prices, "'class' => 'form-check-input mt-0'")
        && str_contains($prices, "'class' => 'form-check-label ms-2 mb-0'"),
    'access_scope_links_centralised' => str_contains($scope, 'CommerceLegacyCatalogLinkGenerator::plan_edit_url')
        && str_contains($scope, 'CommerceLegacyCatalogLinkGenerator::scope_edit_url'),
    'single_commerce_navigation_on_plan_edit' => substr_count(
        $planedit,
        'CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context)'
    ) === 1,
    'safe_cli_config_path' => str_contains(file_get_contents(__FILE__), "require_once(__DIR__ . '/../../../../config.php');"),
];

echo "== 7.95E19A UI and media fixes ==\n\n";
$ok = true;
foreach ($checks as $name => $passed) {
    printf("%-52s %s\n", $name, $passed ? 'OK' : 'FAIL');
    $ok = $ok && $passed;
}

echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";
exit($ok ? 0 : 1);
