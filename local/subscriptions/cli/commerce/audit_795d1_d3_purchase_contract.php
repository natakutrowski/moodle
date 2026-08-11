<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\commerce\purchase\audit\CommerceLegacyPurchaseManagementInventory;
use local_subscriptions\commerce\purchase\contract\CommercePurchaseActionContract;
use local_subscriptions\commerce\purchase\contract\CommercePurchaseListContract;
use local_subscriptions\commerce\purchase\contract\CommercePurchaseViewContract;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;

$root = $CFG->dirroot . '/local/subscriptions/';
$checks = [];
$checks['legacy_architecture_inventoried'] = array_reduce(
    CommerceLegacyPurchaseManagementInventory::files(),
    static fn(bool $carry, string $file): bool => $carry && is_file($root . $file),
    true
);
$checks['list_contract'] = count(CommercePurchaseListContract::fields()) >= 10;
$checks['view_contract'] = in_array('diagnostics', CommercePurchaseViewContract::sections(), true);
$checks['action_contract'] = in_array('retry_fulfillment', CommercePurchaseActionContract::actions(), true);
$checks['native_read_model'] = is_file($root . 'classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php');
$checks['commercial_statuses'] = count(CommerceCommercialStatus::all()) >= 9;
$repositorysource = (string)file_get_contents($root . 'classes/commerce/purchase/readmodel/CommercePurchaseReadRepository.php');
$checks['native_only'] = str_contains($repositorysource, 'CommercePersistenceSchema::TABLE_PURCHASE')
    && !str_contains($repositorysource, 'subscription_plan')
    && !str_contains($repositorysource, 'subscription_access_scope');

fwrite(STDOUT, "== 7.95D1-D3 Unified purchase contracts and read model ==\n\n");
$failed = false;
foreach ($checks as $name => $ok) {
    fwrite(STDOUT, str_pad($name, 34) . ($ok ? 'OK' : 'FAIL') . "\n");
    $failed = $failed || !$ok;
}
fwrite(STDOUT, "\n" . ($failed ? '[FAILED]' : '[CERTIFIED]') . "\n");
exit($failed ? 1 : 0);
