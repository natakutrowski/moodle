<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
[$options] = cli_get_params(['strict' => false, 'help' => false], ['h' => 'help']);
$classes = [
    \local_subscriptions\commerce\command\CommerceCommandFactory::class,
    \local_subscriptions\commerce\idempotency\CommerceIdempotencyService::class,
    \local_subscriptions\commerce\reconciliation\CommerceReconciliationService::class,
    \local_subscriptions\commerce\task\write\CommerceTaskWriteCoordinator::class,
    \local_subscriptions\commerce\command\observability\CommerceWriteObserver::class,
];
cli_writeln('== I10D write rollout audit ==');
$ok = true;
foreach ($classes as $class) { $exists = class_exists($class); $ok = $ok && $exists; cli_writeln(str_pad($class, 92) . ($exists ? '[OK]' : '[MISSING]')); }
cli_writeln('');
foreach ([
 'runtime dual-write' => 'commerce_native_dual_write_enabled',
 'task dual-write' => 'commerce_native_task_dual_write_enabled',
 'shadow write compare' => 'commerce_native_shadow_write_compare_enabled',
 'reconciliation' => 'commerce_native_reconciliation_enabled',
 'repair' => 'commerce_native_repair_enabled',
] as $label => $setting) { cli_writeln(str_pad($label, 24) . (!empty(get_config('local_subscriptions', $setting)) ? 'enabled' : 'disabled')); }
if (!$ok && !empty($options['strict'])) { exit(2); }
cli_writeln('');
cli_writeln($ok ? '[OK] I10D write rollout infrastructure is complete.' : '[WARN] I10D infrastructure is incomplete.');
exit($ok ? 0 : 1);
