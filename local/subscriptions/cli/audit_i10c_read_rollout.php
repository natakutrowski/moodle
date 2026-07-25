<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\read\policy\CommerceReadConsumer;
use local_subscriptions\commerce\read\policy\CommerceReadPolicy;

[$options] = cli_get_params(
    ['strict' => false, 'help' => false],
    ['s' => 'strict', 'h' => 'help']
);

if ($options['help']) {
    echo "Audit I10C read rollout configuration and required classes.\n\n";
    echo "--strict  Return exit code 1 when a required component is missing.\n";
    exit(0);
}

$requiredclasses = [
    \local_subscriptions\commerce\read\CommerceReadServiceFactory::class,
    \local_subscriptions\commerce\read\CommerceReadCoordinatorFactory::class,
    \local_subscriptions\commerce\read\bridge\CommerceLegacyReadBridge::class,
    \local_subscriptions\commerce\read\admin\CommerceAdminReadGateway::class,
    \local_subscriptions\commerce\read\email\CommerceEmailReadGateway::class,
    \local_subscriptions\crm\commerce\I10cCrmCommerceCustomerService::class,
];

$errors = [];

echo "== I10C read rollout audit ==\n\n";
foreach ($requiredclasses as $class) {
    $ok = class_exists($class);
    echo sprintf("%-85s %s\n", $class, $ok ? '[OK]' : '[MISSING]');
    if (!$ok) {
        $errors[] = $class;
    }
}

$policy = new CommerceReadPolicy();
echo "\nFeature flags\n";
foreach (CommerceReadConsumer::all() as $consumer) {
    echo sprintf(
        "  %-10s %s\n",
        $consumer,
        $policy->is_native_enabled($consumer) ? 'native' : 'legacy'
    );
}
echo sprintf("  %-10s %s\n", 'shadow', $policy->is_shadow_enabled() ? 'enabled' : 'disabled');
echo sprintf("  %-10s %s\n", 'fallback', $policy->is_legacy_fallback_enabled() ? 'enabled' : 'disabled');

if ($errors !== []) {
    echo "\n[ERROR] I10C rollout is incomplete.\n";
    exit($options['strict'] ? 1 : 0);
}

echo "\n[OK] I10C read rollout infrastructure is complete.\n";
