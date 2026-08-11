<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f5_rollout_boundary_available' => is_file($root . '/classes/commerce/storefront/rollout/CommerceStorefrontRollout.php'),
    'f5_rollout_setting_available' => str_contains((string)file_get_contents($root . '/settings.php'), 'storefront_enabled'),
    'f5_subscribe_redirect_is_guarded' => str_contains((string)file_get_contents($root . '/subscribe.php'), 'should_redirect_subscribe'),
    'f5_embedded_and_direct_plan_compatibility_preserved' => str_contains((string)file_get_contents($root . '/classes/commerce/storefront/rollout/CommerceStorefrontRollout.php'), '!$embedded')
        && str_contains((string)file_get_contents($root . '/classes/commerce/storefront/rollout/CommerceStorefrontRollout.php'), '$planid === null'),
    'f5_catalogue_uses_native_course_access_filter' => str_contains((string)file_get_contents($root . '/digital_catalog.php'), "'value' => 'course_access'"),
    'f5_f4_test_uses_current_product_type' => str_contains((string)file_get_contents($root . '/tests/commerce/storefront/commerce_795f4_storefront_editor_test.php'), 'CommerceProductType::COURSE_ACCESS'),
];

echo "== 7.95F5 Controlled Storefront rollout ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-68s %s\n", $name, $ok ? 'OK' : 'FAILED');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
