<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6a_merchandising_value_object' => 'classes/commerce/storefront/merchandising/CommerceStorefrontMerchandising.php',
    'f6a_metadata_resolver' => 'classes/commerce/storefront/merchandising/CommerceStorefrontMerchandisingResolver.php',
    'f6a_promotion_resolver' => 'classes/commerce/storefront/merchandising/CommerceStorefrontPromotionResolver.php',
    'f6a_storefront_repository_integration' => 'classes/commerce/storefront/repository/CommerceStorefrontRepository.php',
    'f6a_storefront_presenter_integration' => 'classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php',
    'f6a_crm_editor_integration' => 'admin/commerce/products/storefront.php',
    'f6a_product_card_rendering' => 'templates/storefront/product_card.mustache',
    'f6a_phpunit_coverage' => 'tests/commerce/storefront/commerce_795f6a_merchandising_foundations_test.php',
];

$failed = false;
cli_writeln('== 7.95F6A Merchandising foundations ==');
cli_writeln('');
foreach ($checks as $label => $relativepath) {
    $ok = is_file($root . '/' . $relativepath);
    $failed = $failed || !$ok;
    cli_writeln(str_pad($label, 58) . ($ok ? 'OK' : 'FAIL'));
}

$contentchecks = [
    'f6a_native_metadata_contract' => [
        'classes/commerce/storefront/admin/CommerceStorefrontPageEditor.php',
        "['merchandising']",
    ],
    'f6a_manual_display_order' => [
        'classes/commerce/storefront/repository/CommerceStorefrontRepository.php',
        'get_display_order()',
    ],
    'f6a_featured_products_first' => [
        'classes/commerce/storefront/repository/CommerceStorefrontRepository.php',
        'is_featured()',
    ],
    'f6a_price_bar_rendering' => [
        'templates/storefront/product_card.mustache',
        'compareformatted',
    ],
];
foreach ($contentchecks as $label => [$relativepath, $needle]) {
    $content = file_get_contents($root . '/' . $relativepath);
    $ok = $content !== false && str_contains($content, $needle);
    $failed = $failed || !$ok;
    cli_writeln(str_pad($label, 58) . ($ok ? 'OK' : 'FAIL'));
}

cli_writeln('');
cli_writeln($failed ? '[FAILED]' : '[CERTIFIED]');
exit($failed ? 1 : 0);
