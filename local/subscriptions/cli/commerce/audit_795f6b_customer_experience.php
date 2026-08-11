<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6b_experience_value_object' => is_file($root . '/classes/commerce/storefront/experience/CommerceStorefrontExperience.php'),
    'f6b_experience_resolver' => is_file($root . '/classes/commerce/storefront/experience/CommerceStorefrontExperienceResolver.php'),
    'f6b_native_ownership_resolver' => is_file($root . '/classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php'),
    'f6b_catalogue_group_rendering' => str_contains((string)file_get_contents($root . '/templates/storefront/catalog.mustache'), 'commerce-storefront-group'),
    'f6b_owned_product_cta' => str_contains((string)file_get_contents($root . '/templates/storefront/product_card.mustache'), 'ownedactionurl'),
    'f6b_trust_rendering' => str_contains((string)file_get_contents($root . '/templates/storefront/product_card.mustache'), 'trustitems'),
    'f6b_quick_facts_rendering' => str_contains((string)file_get_contents($root . '/templates/storefront/product_card.mustache'), 'quickfacts'),
    'f6b_crm_editor_integration' => str_contains((string)file_get_contents($root . '/admin/commerce/products/storefront.php'), 'storefront_quickfacts'),
    'f6b_no_legacy_ownership_dependency' => !str_contains((string)file_get_contents($root . '/classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php'), 'subscription_payment'),
    'f6b_phpunit_coverage' => is_file($root . '/tests/commerce/storefront/commerce_795f6b_customer_experience_test.php'),
];

echo "== 7.95F6B Customer experience ==\n\n";
$certified = true;
foreach ($checks as $name => $ok) {
    printf("%-56s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $certified = $certified && $ok;
}
echo "\n[" . ($certified ? 'CERTIFIED' : 'NOT CERTIFIED') . "]\n";
exit($certified ? 0 : 1);
