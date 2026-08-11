<?php

#define('CLI_SCRIPT', true);
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6f_access_scope_validation' => [
        'classes/commerce/catalog/validation/CommerceCatalogActivationValidator.php',
        "metadata['access']",
    ],
    'f6f_product_restore' => [
        'admin/commerce/products/lifecycle.php',
        "action === 'restore'",
    ],
    'f6f_secure_delete' => [
        'admin/commerce/products/lifecycle.php',
        'validate_internal_user_password',
    ],
    'f6f_admin_setting' => [
        'settings.php',
        'commerce_allow_destructive_product_delete',
    ],
    'f6f_sales_detection' => [
        'classes/commerce/catalog/admin/CommerceProductLifecycleService.php',
        'legacyplansales',
    ],
    'f6f_gustave_badge' => [
        'styles/storefront.css',
        'commerce-product-badge__gustave-medallion',
    ],
];

mtrace('== 7.95F6F UX final ==');
mtrace('');
$ok = true;
foreach ($checks as $key => [$file, $needle]) {
    $path = $root . '/' . $file;
    $passed = is_file($path) && str_contains((string)file_get_contents($path), $needle);
    $ok = $ok && $passed;
    mtrace(str_pad($key, 48) . ($passed ? 'OK' : 'FAIL'));
}
mtrace('');
mtrace($ok ? '[CERTIFIED]' : '[FAILED]');
exit($ok ? 0 : 1);
