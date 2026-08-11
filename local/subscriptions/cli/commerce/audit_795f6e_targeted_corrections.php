<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$root = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'f6e_lifecycle_crm_signature' => [
        'file' => $root . '/admin/commerce/products/lifecycle.php',
        'needles' => ['local-subscriptions-commerce-product-lifecycle-page'],
    ],
    'f6e_shared_scope_separation' => [
        'file' => $root . '/admin/commerce/products/access_scope.php',
        'needles' => ["\$action === 'scope'", "\$action === 'canonical'", "metadata['access']"],
        'forbidden' => ['transfer_product('],
    ],
    'f6e_legacy_digital_ownership' => [
        'file' => $root . '/classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php',
        'needles' => ['subscription_digital_payment_request', 'legacy_digital_purchase'],
    ],
    'f6e_gustave_icon_size' => [
        'file' => $root . '/styles/storefront.css',
        'needles' => ['commerce-product-badge__custom-icon--gustave', 'width: 1.8em'],
    ],
];

$failed = false;
echo "== 7.95F6E Targeted corrections ==\n\n";
foreach ($checks as $name => $check) {
    $content = is_file($check['file']) ? file_get_contents($check['file']) : '';
    $ok = $content !== '';
    foreach ($check['needles'] as $needle) {
        $ok = $ok && str_contains($content, $needle);
    }
    foreach ($check['forbidden'] ?? [] as $needle) {
        $ok = $ok && !str_contains($content, $needle);
    }
    printf("%-52s %s\n", $name, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

echo $failed ? "\n[FAILED]\n" : "\n[CERTIFIED]\n";
exit($failed ? 1 : 0);
