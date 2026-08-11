<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\payment\dto\InternalEvent;

[$options, $unrecognized] = cli_get_params([
    'purchase' => '',
    'confirm' => '',
    'help' => false,
], [
    'p' => 'purchase',
    'c' => 'confirm',
    'h' => 'help',
]);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

$reference = trim((string)$options['purchase']);
if ($options['help'] || $reference === '') {
    echo <<<HELP
Commerce 7.95 H4.9 — reprocess a paid Native purchase with no entitlement grants.

Options:
  --purchase=REFERENCE          Native Commerce Purchase reference (required)
  --confirm=REPROCESS-PAID      Required safety confirmation
  --help                        Show this help

Example:
  php local/subscriptions/cli/commerce/operations/reprocess_paid_purchase.php \
    --purchase=cmp_xxx \
    --confirm=REPROCESS-PAID
HELP;
    exit($options['help'] ? 0 : 1);
}

if ((string)$options['confirm'] !== 'REPROCESS-PAID') {
    cli_error('Refusing to write without --confirm=REPROCESS-PAID');
}

$purchase = $DB->get_record(
    CommercePersistenceSchema::TABLE_PURCHASE,
    ['reference' => $reference],
    '*',
    MUST_EXIST
);

$grantcount = $DB->count_records('local_subs_commerce_grant', [
    'purchasereference' => $reference,
]);
if ($grantcount > 0) {
    cli_error(
        'This recovery command is only for paid purchases with zero grants. '
        . 'Use the CRM retry action for an existing partial fulfillment.'
    );
}

$payments = $DB->get_records(
    CommercePersistenceSchema::TABLE_PAYMENT,
    ['purchaseid' => (int)$purchase->id],
    'sequence DESC, id DESC'
);
$payment = null;
foreach ($payments as $candidate) {
    if (in_array((string)$candidate->status, ['paid', 'completed'], true)) {
        $payment = $candidate;
        break;
    }
}
if ($payment === null) {
    cli_error('No paid Native payment exists for this Purchase.');
}

$event = new InternalEvent('checkout_completed', [
    'currency' => (string)$payment->currency,
    'amount_minor' => (int)$payment->amountminor,
    'meta' => [
        'commerce_payment_id' => (int)$payment->id,
        'commerce_purchase_uuid' => (string)$purchase->purchaseuuid,
        'commerce_reference' => $reference,
        'provider' => (string)$payment->provider,
        'recovery_source' => 'commerce_795h49_reprocess_paid_purchase',
    ],
]);

(new CommerceNativePaidPurchaseCompleter(
    $DB,
    new CommercePaymentRepository($DB)
))->complete($event);

echo "Purchase reprocessed successfully: {$reference}
";
echo "Run the relevant certification CLI now.
";
