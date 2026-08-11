<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\audit\nativeux\CommerceNativeUxStaticAuditor;

[$options] = cli_get_params(
    ['json' => false, 'strict' => false],
    ['j' => 'json', 's' => 'strict']
);

$auditor = CommerceNativeUxStaticAuditor::from_plugin_root(__DIR__ . '/../..');
$flags = $auditor->audit_feature_flags();

if ($options['json']) {
    echo json_encode(['phase' => '7.95B0.1', 'flags' => $flags], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

echo "== 7.95B0.1 Commerce feature flags ==\n";
foreach ($flags as $flag) {
    echo sprintf(
        "%-52s %-15s %-30s defined=%s read=%s\n",
        $flag['key'],
        $flag['category'],
        $flag['recommendedstatus'],
        $flag['defined'] ? 'yes' : 'no',
        $flag['read'] ? 'yes' : 'no'
    );
}

$orphans = array_values(array_filter($flags, static fn(array $flag): bool => $flag['defined'] && !$flag['read']));
echo "\nFlags found: " . count($flags) . "\n";
echo "Defined but unread: " . count($orphans) . "\n";

if ($orphans !== [] && $options['strict']) {
    cli_error('Feature flag audit found settings defined but not read.');
}

echo $orphans === [] ? "[OK]\n" : "[WARN]\n";
