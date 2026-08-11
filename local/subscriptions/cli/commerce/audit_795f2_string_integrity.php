<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

$pluginroot = dirname(__DIR__, 2);
$access = file_get_contents($pluginroot . '/db/access.php');
preg_match_all("/'local\\/([^']+)'\\s*=>/", $access, $matches);
$capabilitykeys = array_values(array_unique($matches[1] ?? []));

$checks = [];
foreach (['en', 'fr', 'ru'] as $language) {
    $langfile = file_get_contents($pluginroot . '/lang/' . $language . '/local_subscriptions.php');
    $missing = array_filter(
        $capabilitykeys,
        static fn(string $key): bool => !str_contains($langfile, "\$string['{$key}']")
    );
    $checks['f2fix_' . $language . '_all_capability_strings_exist'] = $missing === [];
}

$catalogue = file_get_contents($pluginroot . '/digital_catalog.php');
$checks['f2fix_storefront_uses_existing_subscription_type_string'] =
    str_contains($catalogue, "commerce_purchase_type_subscription")
    && !str_contains($catalogue, "commerce_type_subscription");

echo "== 7.95F2 string integrity ==\n\n";
$failed = false;
foreach ($checks as $name => $ok) {
    printf("%-70s %s\n", $name, $ok ? 'OK' : 'FAILED');
    $failed = $failed || !$ok;
}
echo "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n";
exit($failed ? 1 : 0);
