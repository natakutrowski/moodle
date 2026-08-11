<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

defined('MOODLE_INTERNAL') || die();

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6i_uniform_badge_height' => [
        $root . '/styles/storefront.css',
        'height: 2.35rem;',
    ],
    'f6i_gustave_medallion_preserved' => [
        $root . '/styles/storefront.css',
        'width: 4.25rem;',
    ],
    'f6i_shared_badge_partial' => [
        $root . '/templates/storefront/product_commerce_panel.mustache',
        '{{> local_subscriptions/storefront/product_badges }}',
    ],
    'f6i_product_header_type_only' => [
        $root . '/templates/storefront/product_templates/default.mustache',
        'commerce-product-type-badge mb-3',
    ],
];

$failed = false;
echo "== 7.95F6I Badge consistency ==

";
foreach ($checks as $name => [$file, $needle]) {
    $ok = is_file($file) && str_contains((string)file_get_contents($file), $needle);
    printf("%-48s %s
", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

echo "
" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "
";
exit($failed ? 1 : 0);
