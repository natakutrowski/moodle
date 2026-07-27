<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$enabled = !empty(get_config('local_subscriptions', 'commerce_native_task_dual_write_enabled'));
cli_writeln('Native task dual-write: ' . ($enabled ? 'enabled' : 'disabled'));
$classes = [
    \local_subscriptions\commerce\task\write\CommerceTaskWriteCoordinator::class,
    \local_subscriptions\commerce\task\write\CommerceTaskWriteFactory::class,
];
foreach ($classes as $class) { cli_writeln($class . (class_exists($class) ? ' [OK]' : ' [MISSING]')); }
exit(array_reduce($classes, fn($ok, $class) => $ok && class_exists($class), true) ? 0 : 2);
