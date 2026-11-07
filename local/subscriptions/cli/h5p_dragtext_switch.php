<?php
// CampusFR DragText user-state guard CLI switch.
define('CLI_SCRIPT', true);
require dirname(__DIR__, 3) . '/config.php';
$key = 'h5p_dragtext_state_guard_enabled';
if (in_array('--on', $argv, true) && in_array('--off', $argv, true)) {
    fwrite(STDERR, "STOP: choose either --on or --off\n");
    exit(1);
}
if (in_array('--on', $argv, true)) { set_config($key, 1, 'local_subscriptions'); }
if (in_array('--off', $argv, true)) { set_config($key, 0, 'local_subscriptions'); }
echo "Site: {$CFG->wwwroot}\n";
echo 'DragText guard: ' . (get_config('local_subscriptions', $key) ? 'ON' : 'OFF') . "\n";
echo "Existing browser tabs need a reload.\n";
