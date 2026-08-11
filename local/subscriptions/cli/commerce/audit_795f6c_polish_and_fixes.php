<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
$root=$CFG->dirroot.'/local/subscriptions/';
$checks=[
 'f6c_sku_generator'=>is_file($root.'classes/commerce/catalog/admin/CommerceProductSkuGenerator.php'),
 'f6c_access_scope_linking'=>str_contains(file_get_contents($root.'admin/commerce/products/access_scope.php'),'legacyplanid'),
 'f6c_ownership_limit_fix'=>!str_contains(file_get_contents($root.'classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php'),'LIMIT 1'),
 'f6c_core_text_fix'=>str_contains(file_get_contents($root.'classes/commerce/storefront/experience/CommerceStorefrontExperienceResolver.php'),'use core_text;'),
 'f6c_badge_icons'=>str_contains(file_get_contents($root.'classes/commerce/storefront/presentation/CommerceStorefrontPresenter.php'),'badge_icon'),
 'f6c_recommendations'=>is_file($root.'classes/commerce/storefront/recommendation/CommerceStorefrontRecommendationService.php'),
 'f6c_sellability_inspector'=>is_file($root.'classes/commerce/catalog/validation/CommerceProductSellabilityInspector.php'),
 'f6c_phpunit'=>is_file($root.'tests/commerce/storefront/commerce_795f6c_polish_and_fixes_test.php'),
];
echo "== 7.95F6C Visual polish, recommendations and findings ==

";
$ok=true; foreach($checks as $name=>$value){echo str_pad($name,58).($value?'OK':'FAIL')."
";$ok=$ok&&$value;}
echo "
".($ok?'[CERTIFIED]':'[NOT CERTIFIED]')."
"; exit($ok?0:1);
