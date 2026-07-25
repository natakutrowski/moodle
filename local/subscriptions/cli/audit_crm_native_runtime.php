<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\crm\commerce\LegacyCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\NativeCrmCommerceCustomerService;
use local_subscriptions\crm\commerce\shadow\CrmCommerceSnapshotComparator;

[$options] = cli_get_params(['userid' => 0, 'strict' => false, 'verbose' => false], ['u' => 'userid']);
$userid = (int)$options['userid'];
if ($userid <= 0) { cli_error('Use --userid=<positive Moodle user id>.'); }
$user = $DB->get_record('user', ['id' => $userid], 'id,email', MUST_EXIST);
$legacy = (new LegacyCrmCommerceCustomerService())->build_snapshot($userid, $user->email);
$native = (new NativeCrmCommerceCustomerService())->build_snapshot($userid, $user->email);
$comparison = (new CrmCommerceSnapshotComparator())->compare($native, $legacy);
$isequivalent = $comparison->is_equivalent();
$status = $isequivalent ? 'equal' : 'different';

echo "== I8 CRM native runtime audit ==\n";
echo "User: {$userid}\n";
$legacypurchasecount =
    $legacy->get_subscription_count()
    + $legacy->get_digital_purchase_count();

$nativepurchasecount =
    $native->get_subscription_count()
    + $native->get_digital_purchase_count();

echo "Legacy purchases: {$legacypurchasecount}\n";
echo "Native purchases: {$nativepurchasecount}\n";
echo "Status: {$status}\n";

if ($options['verbose']) {
    foreach ($comparison->get_differences() as $difference) {
        echo '  - ' . $difference->get_field() . "\n";

        echo '    Native: ' . json_encode(
            $difference->get_commerce_value(),
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) . "\n";

        echo '    Legacy: ' . json_encode(
            $difference->get_legacy_value(),
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) . "\n";
    }
}

if (!$isequivalent && $options['strict']) {
    cli_error('CRM Native snapshot differs from Legacy.');
}

echo $isequivalent
    ? "[OK]\n"
    : "[WARN]\n";
