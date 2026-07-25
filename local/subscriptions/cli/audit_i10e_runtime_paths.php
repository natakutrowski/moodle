<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
use local_subscriptions\commerce\rollout\CommerceRuntimePathRegistry;
use local_subscriptions\commerce\dualwrite\CommerceDualWriteFeatureToggle;
cli_writeln('== I10E runtime path matrix ==');
cli_writeln('');
$root = $CFG->dirroot . '/local/subscriptions/';
$missing = 0;
foreach ((new CommerceRuntimePathRegistry())->all() as $path) {
    $ok = true;
    foreach ($path->get_files() as $file) { $ok = $ok && is_file($root . $file); }
    if (!$ok) { $missing++; }
    cli_writeln(sprintf('  %-32s %-12s %-9s [%s]', $path->get_key(), $path->get_family(), $path->get_risk(), $ok ? 'OK' : 'MISSING'));
}
$toggle = new CommerceDualWriteFeatureToggle();
cli_writeln('');
cli_writeln('Effective runtime dual-write: ' . ($toggle->is_enabled() ? 'enabled via ' . $toggle->effective_source() : 'disabled'));
if ($missing > 0) { cli_error('I10E runtime path matrix is incomplete.'); }
cli_writeln('[OK] I10E runtime paths are present.');
