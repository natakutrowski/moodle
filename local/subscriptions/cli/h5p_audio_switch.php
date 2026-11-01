<?php
// CampusFR H5P switches: inspection and emergency OFF, no H5P library operations.
define('CLI_SCRIPT', true);
require dirname(__DIR__, 3) . '/config.php';
require_once($CFG->libdir . '/clilib.php');

[$options, $unknown] = cli_get_params([
    'audio' => null,
    'memory' => null,
    'help' => false,
], ['h' => 'help']);

if ($options['help']) {
    echo "Usage: php local/subscriptions/cli/h5p_audio_switch.php [--audio=on|off] [--memory=on|off]\n";
    echo "With no arguments, print the current settings. OFF leaves Moodle's H5P scripts untouched.\n";
    exit(0);
}
if ($unknown) {
    cli_error('Unknown arguments: ' . implode(', ', $unknown));
}
$keys = [
    'audio' => 'h5p_audio_campusfr_enabled',
    'memory' => 'h5p_memory_campusfr_enabled',
];
foreach ($keys as $name => $key) {
    if ($options[$name] !== null) {
        if (!in_array($options[$name], ['on', 'off'], true)) {
            cli_error('Invalid --' . $name . ': expected on or off.');
        }
    }
}
foreach ($keys as $name => $key) {
    if ($options[$name] !== null) {
        set_config($key, $options[$name] === 'on' ? 1 : 0, 'local_subscriptions');
    }
}
echo 'Site: ' . $CFG->wwwroot . PHP_EOL;
foreach ($keys as $name => $key) {
    echo $name . ': ' . ((string)get_config('local_subscriptions', $key) === '1' ? 'ON' : 'OFF') . PHP_EOL;
}
echo "Existing H5P browser tabs must be reloaded for changes to apply.\n";
