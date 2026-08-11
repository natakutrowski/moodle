<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$pluginroot = $CFG->dirroot . '/local/subscriptions';
$checks = [
    'action_policy' => ['classes/commerce/purchase/action/CommercePurchaseActionPolicy.php', ['can_retry_fulfillment', 'destructive_actions_available']],
    'native_action_service' => ['classes/commerce/purchase/action/CommercePurchaseActionService.php', ['CommerceNativePurchaseFulfillmentOrchestrator', 'AdminLog::log']],
    'sesskey_endpoints' => ['admin/commerce/purchases/retry_fulfillment.php', ['require_sesskey', 'required_param']],
    'internal_notes' => ['admin/commerce/purchases/add_note.php', ['require_sesskey', 'add_note']],
    'unified_navigation' => ['classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php', ["self::PURCHASES", 'purchases/index.php']],
    'legacy_redirector' => ['classes/commerce/purchase/compatibility/CommerceLegacyPurchaseRedirector.php', ['find_by_legacy', 'view_url']],
    'subscription_redirects' => ['admin/subscriptions/index.php', ['CommerceLegacyPurchaseRedirector', 'redirect']],
    'digital_redirects' => ['admin/digital/purchases/index.php', ['CommerceLegacyPurchaseRedirector', 'redirect']],
];

$failed = false;
echo "== 7.95D7-D10 Unified purchase actions and compatibility ==\n\n";
foreach ($checks as $label => [$relative, $needles]) {
    $path = $pluginroot . '/' . $relative;
    $content = is_file($path) ? (string)file_get_contents($path) : '';
    $ok = $content !== '';
    foreach ($needles as $needle) { $ok = $ok && str_contains($content, $needle); }
    printf("%-32s %s\n", $label, $ok ? 'OK' : 'FAIL');
    $failed = $failed || !$ok;
}

$service = (string)file_get_contents($pluginroot . '/classes/commerce/purchase/action/CommercePurchaseActionService.php');
$nativeonly = !str_contains($service, 'DigitalPurchaseAdminActionService') && !str_contains($service, 'subscription_plan');
printf("%-32s %s\n", 'native_only', $nativeonly ? 'OK' : 'FAIL');
$failed = $failed || !$nativeonly;

echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
