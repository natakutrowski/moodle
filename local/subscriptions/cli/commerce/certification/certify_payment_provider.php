<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(['provider' => '', 'purchase' => '', 'json' => false, 'help' => false], ['r' => 'provider', 'p' => 'purchase', 'j' => 'json', 'h' => 'help']);
if ($unrecognized) { cli_error('Unknown options: ' . implode(', ', $unrecognized)); }
if ($options['help'] || strtolower(trim((string)$options['provider'])) !== 'alfa') {
    echo "Commerce payment-provider certification\n\n  --provider=alfa       Provider to certify\n  --purchase=REFERENCE  Optional real paid Purchase\n  --json                Output JSON\n";
    exit($options['help'] ? 0 : 1);
}
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/certify_alfa.php');
if (trim((string)$options['purchase']) !== '') { $command .= ' --purchase=' . escapeshellarg((string)$options['purchase']); }
if ($options['json']) { $command .= ' --json'; }
passthru($command, $exitcode);
exit($exitcode);
