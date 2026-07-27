<?php

define('CLI_SCRIPT', true);
require dirname(__DIR__, 5) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\runtime\switching\CommerceRuntimeConfiguration;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeMode;

[$options] = cli_get_params(['mode' => null, 'confirm-native' => false, 'help' => false], ['h' => 'help']);
if ($options['help'] || $options['mode'] === null) {
    cli_writeln("Usage: php set_commerce_runtime_mode.php --mode=legacy|shadow|native [--confirm-native]");
    exit($options['help'] ? 0 : 2);
}
$mode = CommerceRuntimeMode::normalize((string)$options['mode']);
if ($mode === CommerceRuntimeMode::NATIVE && !$options['confirm-native']) {
    cli_error('Native mode requires --confirm-native.');
}
(new CommerceRuntimeConfiguration())->set_mode($mode);
cli_writeln('Commerce runtime mode: ' . $mode);
