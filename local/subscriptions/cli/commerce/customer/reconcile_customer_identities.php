<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'limit' => 0,
        'email' => null,
    ],
    ['h' => 'help']
);

if ($unrecognized) {
    cli_error("Unknown option(s):\n  " . implode("\n  ", $unrecognized));
}

if ($options['help']) {
    echo <<<TXT
Reconcile account-less Native Commerce purchases with Moodle users by exact email.

Dry-run is the default. Use --execute to persist changes.

Usage:
  php local/subscriptions/cli/commerce/customer/reconcile_customer_identities.php
  php local/subscriptions/cli/commerce/customer/reconcile_customer_identities.php --limit=100
  php local/subscriptions/cli/commerce/customer/reconcile_customer_identities.php --email=client@example.com
  php local/subscriptions/cli/commerce/customer/reconcile_customer_identities.php --execute

Options:
  --execute       Persist reconciliations. Without it, no data is changed.
  --limit=N       Process at most N unresolved Native purchases (0 = unlimited).
  --email=EMAIL   Restrict the run to one buyer email.
  -h, --help      Show this help.

TXT;
    exit(0);
}

$execute = !empty($options['execute']);
$limit = max(0, (int)$options['limit']);
$email = $options['email'] !== null ? trim((string)$options['email']) : null;
if ($email !== null && $email !== '' && !validate_email($email)) {
    cli_error('Invalid --email value.');
}

$service = new CommerceCustomerIdentityReconciliationService($DB);
$results = $service->reconcile_batch($limit, $execute, $email);

mtrace($execute ? 'MODE: EXECUTE' : 'MODE: DRY-RUN');
mtrace('Native purchases inspected: ' . count($results));
mtrace(str_repeat('-', 100));

$counters = [
    CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_MATCHED => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_UNCHANGED => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_NOT_FOUND => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_AMBIGUOUS => 0,
    CommerceCustomerIdentityReconciliationResult::STATUS_SKIPPED => 0,
];
$grants = $access = $guests = $legacy = 0;

foreach ($results as $result) {
    $counters[$result->status] = ($counters[$result->status] ?? 0) + 1;
    $grants += $result->grantsupdated;
    $access += $result->digitalaccessupdated;
    $guests += $result->guestsessionsupdated;
    $legacy += $result->legacyrecordsupdated;

    $target = $result->userid !== null ? 'userid=' . $result->userid : '-';
    if ($result->candidateuserids !== []) {
        $target = 'candidates=' . implode(',', $result->candidateuserids);
    }
    mtrace(sprintf(
        '#%d %-32s %-14s %s',
        (int)$result->purchaseid,
        (string)$result->email,
        strtoupper($result->status),
        $target
    ));
}

mtrace(str_repeat('-', 100));
foreach ($counters as $status => $count) {
    mtrace(ucfirst(str_replace('_', ' ', $status)) . ': ' . $count);
}
if ($execute) {
    mtrace("Grant rows updated: {$grants}");
    mtrace("Digital access rows updated: {$access}");
    mtrace("Guest session rows updated: {$guests}");
    mtrace("Legacy digital rows updated: {$legacy}");
}
mtrace('Done.');
