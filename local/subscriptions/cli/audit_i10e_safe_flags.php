<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
use local_subscriptions\commerce\rollout\CommerceRolloutGuard;
$guard = new CommerceRolloutGuard();
$guard->assert_safe_configuration();
cli_writeln('== I10E safe flag audit ==');
foreach ($guard->state() as $name => $enabled) { cli_writeln(sprintf('  %-24s %s', $name, $enabled ? 'enabled' : 'disabled')); }
cli_writeln('[OK] I10E flag combination is safe.');
