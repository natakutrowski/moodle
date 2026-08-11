<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6g_complete_password_user_record' => [
        'admin/commerce/products/lifecycle.php',
        "$DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST)",
    ],
    'f6g_optional_password_parameter' => [
        'admin/commerce/products/lifecycle.php',
        "optional_param('adminpassword', '', PARAM_RAW)",
    ],
    'f6g_destructive_setting_guard' => [
        'admin/commerce/products/lifecycle.php',
        "if ($force && !get_config('local_subscriptions'",
    ],
    'f6g_type_badge_alignment' => [
        'styles/storefront.css',
        '.commerce-product-type-badge',
    ],
    'f6g_gustave_medallion_spacing' => [
        'styles/storefront.css',
        'padding: .42rem .95rem .42rem 3.35rem',
    ],
    'f6g_phpunit' => [
        'tests/commerce/storefront/commerce_795f6g_final_polish_test.php',
        'commerce_795f6g_final_polish_test',
    ],
];

mtrace('== 7.95F6G Final polish ==');
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
