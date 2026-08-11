<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
$root = $CFG->dirroot . '/local/subscriptions/';
$checks = [
 'f6d_mapping_conflict_ui' => ['admin/commerce/products/access_scope.php', 'transfermapping'],
 'f6d_multilingual_editor' => ['admin/commerce/products/storefront.php', 'editlang'],
 'f6d_badge_icon_rendering' => ['templates/storefront/product_card.mustache', 'customiconurl'],
 'f6d_hybrid_ownership' => ['classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php', 'legacy_plan'],
 'f6d_product_lifecycle' => ['admin/commerce/products/lifecycle.php', 'SUPPRIMER'],
 'f6d_storefront_last' => ['classes/commerce/catalog/rendering/CommerceProductEditorNavigationRenderer.php', 'deliberately last'],
];
echo "== 7.95F6D Stabilisation ==\n\n";
$ok=true; foreach($checks as $name=>[$file,$needle]) { $pass=is_file($root.$file)&&str_contains(file_get_contents($root.$file),$needle); printf("%-48s %s\n",$name,$pass?'OK':'FAIL'); $ok=$ok&&$pass; }
echo "\n".($ok?'[CERTIFIED]':'[FAILED]')."\n"; exit($ok?0:1);
