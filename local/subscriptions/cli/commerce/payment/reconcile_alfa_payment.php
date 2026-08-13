<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\reconciliation\alfa\AlfaPaymentReconciliationService;

[$options] = cli_get_params(
    [
        'help' => false,
        'reference' => '',
        'paymentid' => 0,
        'execute' => false,
    ],
    ['h' => 'help']
);

if (!empty($options['help']) || ((string)$options['reference'] === '' && (int)$options['paymentid'] <= 0)) {
    echo <<<TXT
CampusFR Alfa Payment Reconciliation

Reads Alfa live and compares the provider state with one Native Commerce payment.
The command is READ-ONLY unless --execute is explicitly supplied.

Options:
  --reference=cmp_xxx   Campus Commerce purchase reference.
  --paymentid=123       Native Commerce payment attempt ID.
  --execute             Run the normal Commerce finalization pipeline when safe.
  -h, --help            Show this help.

Examples:
  php local/subscriptions/cli/commerce/payment/reconcile_alfa_payment.php --reference=cmp_xxx
  php local/subscriptions/cli/commerce/payment/reconcile_alfa_payment.php --reference=cmp_xxx --execute

TXT;
    exit(empty($options['help']) ? 1 : 0);
}

global $DB;
$service = AlfaPaymentReconciliationService::create($DB);

try {
    $inspection = (string)$options['reference'] !== ''
        ? $service->inspect_purchase_reference((string)$options['reference'])
        : $service->inspect_payment((int)$options['paymentid']);

    $print = static function($i): void {
        echo "Purchase          : {$i->purchasereference}\n";
        echo "Payment ID        : {$i->paymentid}\n";
        echo "Alfa order ID     : {$i->providerorderid}\n";
        echo "Campus payment    : {$i->campuspaymentstatus}\n";
        echo "Campus purchase   : {$i->campuspurchasestatus}\n";
        echo "Campus amount     : {$i->campusamountminor} {$i->campuscurrency}\n";
        echo "Alfa orderStatus  : " . ($i->provider->orderstatus ?? '-') . "\n";
        echo "Alfa paymentState : " . ($i->provider->paymentstate ?? '-') . "\n";
        echo "Alfa amount       : " . ($i->provider->amountminor ?? '-') . ' ' . ($i->provider->currency ?? '-') . "\n";
        echo "Alfa deposited    : " . ($i->provider->depositedamountminor ?? '-') . "\n";
        echo "Amount match      : " . ($i->amountmatches ? 'YES' : 'NO') . "\n";
        echo "Currency match    : " . ($i->currencymatches ? 'YES' : 'NO') . "\n";
        echo "Approved match    : " . ($i->approvedamountmatches ? 'YES' : 'NO') . "\n";
        echo "Deposit match     : " . ($i->depositedamountmatches ? 'YES' : 'NO') . "\n";
        echo "Provider paid     : " . ($i->providerpaid ? 'YES' : 'NO') . "\n";
        echo "Already complete  : " . ($i->alreadycomplete ? 'YES' : 'NO') . "\n";
        echo "Reconcilable      : " . ($i->reconcilable ? 'YES' : 'NO') . "\n";
        echo "Blockers          : " . ($i->blockers ? implode(', ', $i->blockers) : '-') . "\n";
    };

    echo "=== ALFA RECONCILIATION CHECK ===\n";
    $print($inspection);

    if (empty($options['execute'])) {
        echo "\nREAD-ONLY: no Campus data changed.\n";
        exit(0);
    }
    if ($inspection->alreadycomplete) {
        echo "\nNothing to do: the payment is already fully reconciled.\n";
        exit(0);
    }
    if (!$inspection->reconcilable) {
        cli_error('Reconciliation refused: provider/Campus checks did not all pass.');
    }

    echo "\nExecuting normal Commerce reconciliation pipeline...\n";
    $after = (string)$options['reference'] !== ''
        ? $service->reconcile_payment($inspection->paymentid)
        : $service->reconcile_payment((int)$options['paymentid']);
    echo "\n=== RESULT ===\n";
    $print($after);
    if (!$after->alreadycomplete) {
        cli_error('The reconciliation pipeline returned without a fully completed purchase.');
    }
    echo "\nSUCCESS: payment and purchase are fully reconciled.\n";
} catch (Throwable $e) {
    cli_error($e->getMessage());
}
