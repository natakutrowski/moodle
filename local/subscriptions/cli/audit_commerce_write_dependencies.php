<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$root = realpath(__DIR__ . '/..');
$patterns = [
    'DIRECT_LEGACY_WRITE' => '/\b(insert_record|update_record|set_field|delete_records)\s*\(/',
    'DUAL_WRITE' => '/CommerceDualWrite(?:Bridge|Factory|Service)/',
    'COMMAND_SERVICE' => '/Commerce(?:Purchase)?CommandService|CommerceCommandFactory/',
    'IDEMPOTENCY' => '/idempotenc/i',
];

$results = array_fill_keys(array_keys($patterns), []);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $relative = ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
    $contents = file_get_contents($path) ?: '';

    foreach ($patterns as $category => $pattern) {
        if (preg_match($pattern, $contents)) {
            $results[$category][] = $relative;
        }
    }
}

foreach ($results as $category => $files) {
    sort($files);
    cli_writeln($category . ': ' . count($files));
    foreach ($files as $file) {
        cli_writeln('  - ' . $file);
    }
}
