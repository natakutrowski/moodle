<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

$pluginroot = dirname(__DIR__, 2);
$checks = [
    'customer_actions_inline' => [
        $pluginroot . '/admin/commerce/purchases/view.php',
        'd-flex flex-wrap gap-2 mt-2',
    ],
    'payment_requests_collapsible' => [
        $pluginroot . '/admin/commerce/purchases/view.php',
        'commerce_purchase_payment_requests_section',
    ],
    'payment_request_anchor' => [
        $pluginroot . '/admin/commerce/purchases/view.php',
        'commerce-payment-request-',
    ],
    'list_retry_action' => [
        $pluginroot . '/admin/commerce/purchases/index.php',
        'retry_fulfillment.php',
    ],
    'retry_failed_summary' => [
        $pluginroot . '/classes/commerce/purchase/action/CommercePurchaseActionPolicy.php',
        'partially_fulfilled',
    ],
    'payment_request_allowlist' => [
        $pluginroot . '/classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php',
        'payment_request_details',
    ],
];

echo "== 7.95D14 Unified sales polish ==\n\n";

$ok = true;

foreach ($checks as $label => [$file, $needle]) {
    $content = is_file($file) ? file_get_contents($file) : false;
    $pass = is_string($content) && str_contains($content, $needle);

    printf("%-34s %s\n", $label, $pass ? 'OK' : 'FAIL');
    $ok = $ok && $pass;
}

echo "\n" . ($ok ? '[CERTIFIED]' : '[FAILED]') . "\n";

exit($ok ? 0 : 1);