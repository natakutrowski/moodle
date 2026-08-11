<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(['json' => false], ['j' => 'json']);
$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
$map = $auditor->native_architecture_map();

if ($options['json']) {
    echo json_encode(['phase' => '7.95B0.6', 'architecture' => $map], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "== 7.95B0.6 Native UX architecture map ==\n";
foreach ($map as $layer => $items) {
    echo "\n" . strtoupper(str_replace('_', ' ', $layer)) . "\n";
    foreach ($items as $item) {
        echo "  - {$item}\n";
    }
}
echo "\n[OK]\n";
