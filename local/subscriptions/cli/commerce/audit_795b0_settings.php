<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(['json' => false, 'commerce-only' => false], ['j' => 'json', 'c' => 'commerce-only']);
$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
$settings = $auditor->audit_settings();

if ($options['commerce-only']) {
    $settings = array_values(array_filter($settings, static fn(array $setting): bool => $setting['commerce']));
}

if ($options['json']) {
    echo json_encode(['phase' => '7.95B0.2', 'settings' => $settings], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "== 7.95B0.2 Commerce settings map ==\n";
$currentcategory = null;
foreach ($settings as $setting) {
    if ($setting['category'] !== $currentcategory) {
        $currentcategory = $setting['category'];
        echo "\n[" . strtoupper($currentcategory) . "]\n";
    }
    echo sprintf(
        "%-48s %-36s line=%-4d %s\n",
        $setting['key'],
        $setting['type'],
        $setting['line'],
        $setting['recommendedstatus']
    );
}

echo "\nSettings found: " . count($settings) . "\n[OK]\n";
