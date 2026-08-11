<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(['json' => false, 'strict-ui' => false], ['j' => 'json', 's' => 'strict-ui']);
$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
$dependencies = $auditor->audit_legacy_dependencies();

if ($options['json']) {
    echo json_encode(['phase' => '7.95B0.4', 'dependencies' => $dependencies], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "== 7.95B0.4 Legacy dependency inventory ==\n";
$uicount = 0;
foreach ($dependencies as $dependency) {
    if ($dependency['layer'] === 'ui') {
        $uicount++;
    }
    echo sprintf("[%s] %-75s findings=%d\n", strtoupper($dependency['layer']), $dependency['file'], count($dependency['findings']));
}

echo "\nFiles with Legacy references: " . count($dependencies) . "\n";
echo "UI files with Legacy references: {$uicount}\n";
echo "Compatibility-layer findings are accepted until 7.99.\n";
if ($uicount > 0 && $options['strict-ui']) {
    cli_error('Legacy dependencies remain in the UI layer.');
}
echo $uicount === 0 ? "[OK]\n" : "[WARN]\n";
