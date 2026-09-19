<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureCertificationService;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureInspector;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;

cli_heading('Commerce 7.96E13 payment closure certification');

$errors = [];
$warnings = [];

try {
    $registry = CommercePaymentProviderRegistryFactory::create();
    $currencies = new CommerceCurrencyRegistry();

    $architecture = (
        new CommercePaymentArchitectureCertificationService(
            $registry,
            $currencies
        )
    )->certify();

    $inspector = new CommercePaymentArchitectureInspector(
        $registry
    );

    foreach ($architecture['errors'] as $issue) {
        $errors[] = '[architecture] '
            . $issue['code']
            . ': '
            . $issue['message'];
    }

    foreach ($architecture['warnings'] as $issue) {
        $warnings[] = '[architecture] '
            . $issue['code']
            . ': '
            . $issue['message'];
    }

    $providers = [];
    foreach ($inspector->providers() as $provider) {
        $providers[(string)$provider['key']] = $provider;
    }

    foreach (['stripe', 'alfa'] as $providerkey) {
        if (!isset($providers[$providerkey])) {
            $errors[] = '[provider] Missing current provider: '
                . $providerkey;
            continue;
        }

        $provider = $providers[$providerkey];

        if (empty($provider['refundcertified'])) {
            $errors[] = '[refund] Provider is not refund-certified: '
                . $providerkey;
        }

        if (empty($provider['refundhistorycontract'])) {
            $errors[] = '[refund-history] Provider cannot synchronize refunds: '
                . $providerkey;
        }

        cli_writeln(
            sprintf(
                '[PROVIDER] %s | available=%s | refunds=%s | refund-history=%s',
                $providerkey,
                !empty($provider['available']) ? 'yes' : 'no',
                !empty($provider['refundcertified']) ? 'yes' : 'no',
                !empty($provider['refundhistorycontract']) ? 'yes' : 'no'
            )
        );
    }

    $requiredfiles = [
        'admin/commerce/purchases/reconcile_alfa.php',
        'admin/commerce/purchases/reconcile_stripe.php',
        'classes/commerce/payment/reconciliation/alfa/AlfaPaymentReconciliationService.php',
        'classes/commerce/payment/reconciliation/stripe/StripePaymentReconciliationService.php',
        'classes/commerce/payment/refund/CommercePaymentRefundImportService.php',
    ];

    foreach ($requiredfiles as $relativepath) {
        if (!is_file($CFG->dirroot . '/local/subscriptions/' . $relativepath)) {
            $errors[] = '[surface] Missing E payment surface: '
                . $relativepath;
        }
    }

    $purchasesindex = file_get_contents(
        $CFG->dirroot
        . '/local/subscriptions/admin/commerce/purchases/index.php'
    );

    foreach (['reconcile_alfa.php', 'reconcile_stripe.php'] as $route) {
        if (!str_contains($purchasesindex, $route)) {
            $errors[] = '[crm] Purchases index does not expose '
                . $route;
        }
    }

    $purchaseview = file_get_contents(
        $CFG->dirroot
        . '/local/subscriptions/admin/commerce/purchases/view.php'
    );

    foreach (['reconcile_alfa.php', 'reconcile_stripe.php'] as $route) {
        if (!str_contains($purchaseview, $route)) {
            $errors[] = '[crm] Purchase view does not expose '
                . $route;
        }
    }
} catch (\Throwable $exception) {
    $errors[] = '[fatal] ' . $exception->getMessage();
}

foreach ($warnings as $warning) {
    cli_writeln('[WARN] ' . $warning);
}

foreach ($errors as $error) {
    cli_writeln('[ERROR] ' . $error);
}

cli_writeln('');
cli_writeln('Errors: ' . count($errors));
cli_writeln('Warnings: ' . count($warnings));
cli_writeln(
    $errors === []
        ? 'Commerce 7.96E closure: PASS'
        : 'Commerce 7.96E closure: FAIL'
);

exit($errors === [] ? 0 : 1);
