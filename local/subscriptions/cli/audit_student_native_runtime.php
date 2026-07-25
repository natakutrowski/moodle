<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\student\StudentCommercePurchaseFactory;

[$options] = cli_get_params(['userid' => 0, 'strict' => false, 'verbose' => false], ['u' => 'userid']);
$userid = (int)$options['userid'];
if ($userid <= 0) { cli_error('Use --userid=<positive Moodle user id>.'); }
$user = $DB->get_record('user', ['id' => $userid], 'id,email', MUST_EXIST);
$legacy = StudentCommercePurchaseFactory::create('legacy', false)->get_for_customer($userid, $user->email);
$native = StudentCommercePurchaseFactory::create('native', false)->get_for_customer($userid, $user->email);
$shadow = StudentCommercePurchaseFactory::create('shadow', false)->get_for_customer($userid, $user->email);

echo "== I9 Student native runtime audit ==\n";
echo "User: {$userid}\n";
echo 'Legacy subscriptions: ' . count($legacy->get_subscriptions()) . "\n";
echo 'Native subscriptions: ' . count($native->get_subscriptions()) . "\n";
echo 'Legacy digital purchases: ' . count($legacy->get_digital_purchases()) . "\n";
echo 'Native digital purchases: ' . count($native->get_digital_purchases()) . "\n";
echo 'Status: ' . ($shadow->is_equivalent() ? 'equal' : 'different') . "\n";
if ($options['verbose']) {
    foreach ($shadow->get_differences() as $difference) { echo "  - {$difference}\n"; }
}
if (!$shadow->is_equivalent() && $options['strict']) { cli_error('Student Native projection differs from Legacy.'); }
echo $shadow->is_equivalent() ? "[OK]\n" : "[WARN]\n";
