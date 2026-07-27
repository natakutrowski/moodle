<?php

define('CLI_SCRIPT', true);

require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(['help' => false, 'strict' => false, 'json' => false], ['h' => 'help']);
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    cli_writeln('Usage: php local/subscriptions/cli/maintenance/tooling/audit_h0_tooling_phase.php [--strict] [--json]');
    exit(0);
}

$pluginroot = dirname(__DIR__, 3);
$cliroot = $pluginroot . '/cli';
$testroot = $pluginroot . '/tests';
$manifest = require __DIR__ . '/h0def_cleanup_manifest.php';
$checks = [];

$rootphp = glob($cliroot . '/*.php') ?: [];
$checks['cli_root'] = count($rootphp) === 0;
$rootests = glob($testroot . '/*.php') ?: [];
$checks['test_root'] = count($rootests) === 0;
$checks['documentation'] = is_file($pluginroot . '/docs/tooling/H0_TOOLING_POLICY.md') && is_file($cliroot . '/README.md');
$checks['obsolete_tools'] = true;
foreach ($manifest as $relativepath) {
    if (file_exists($cliroot . '/' . $relativepath)) {
        $checks['obsolete_tools'] = false;
        break;
    }
}
$checks['cli_bootstrap'] = true;
$checks['no_html'] = true;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cliroot, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    if (strtolower($file->getExtension()) === 'html') {
        $checks['no_html'] = false;
    }
    if (strtolower($file->getExtension()) !== 'php' || str_ends_with($file->getFilename(), '_manifest.php')) {
        continue;
    }
    $contents = file_get_contents($file->getPathname());
    if (strpos($contents, "define('CLI_SCRIPT', true)") === false && strpos($contents, 'define("CLI_SCRIPT", true)') === false) {
        continue;
    }
    if (strpos($contents, 'clilib.php') === false) {
        $checks['cli_bootstrap'] = false;
    }
}
$errors = count(array_filter($checks, static fn(bool $ok): bool => !$ok));
$result = ['phase' => '7.94H0', 'checks' => $checks, 'errors' => $errors, 'certified' => $errors === 0];
if ($options['json']) {
    cli_writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
} else {
    cli_writeln('== Phase 7.94H0 - Test & CLI Cleanup ==');
    cli_writeln('');
    foreach ($checks as $name => $ok) {
        cli_writeln(str_pad($name . ':', 24) . ($ok ? 'OK' : 'ERROR'));
    }
    cli_writeln(str_pad('errors:', 24) . $errors);
    cli_writeln(str_pad('certified:', 24) . ($errors === 0 ? 'yes' : 'no'));
}
if ($options['strict'] && $errors > 0) {
    exit(1);
}
