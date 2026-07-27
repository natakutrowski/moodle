<?php

define('CLI_SCRIPT', true);

require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'execute' => false,
    'confirm-h0def-cleanup' => false,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    cli_writeln("Phase 7.94H0D cleanup\n\nDry-run:\n  php local/subscriptions/cli/maintenance/tooling/apply_h0def_cleanup.php\n\nExecute:\n  php local/subscriptions/cli/maintenance/tooling/apply_h0def_cleanup.php --execute --confirm-h0def-cleanup");
    exit(0);
}
if ($options['execute'] && !$options['confirm-h0def-cleanup']) {
    cli_error('--confirm-h0def-cleanup is required with --execute.');
}

$cliroot = dirname(__DIR__, 2);
$manifest = require __DIR__ . '/h0def_cleanup_manifest.php';
$removed = 0;
$missing = 0;
foreach ($manifest as $relativepath) {
    $path = $cliroot . '/' . $relativepath;
    if (!is_file($path)) {
        cli_writeln('[ABSENT] ' . $relativepath);
        $missing++;
        continue;
    }
    if (!$options['execute']) {
        cli_writeln('[WOULD DELETE] ' . $relativepath);
        continue;
    }
    if (!unlink($path)) {
        cli_error('Unable to delete: ' . $relativepath);
    }
    cli_writeln('[DELETED] ' . $relativepath);
    $removed++;
}
cli_writeln('');
cli_writeln($options['execute'] ? "Deleted: {$removed}" : 'Dry-run complete.');
cli_writeln("Already absent: {$missing}");
