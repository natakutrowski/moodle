<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureCertificationService;
use local_subscriptions\commerce\payment\provider\CommercePaymentArchitectureInspector;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
        's' => 'strict',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: ' . implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Audit the generic Commerce payment architecture.

This audit is provider-agnostic. It validates provider keys, currencies,
payment methods, refund contracts, refund-history contracts and ambiguous
provider priorities.

Options:
-h, --help       Display this help.
-s, --strict     Return non-zero for warnings as well as errors.

Example:
php local/subscriptions/cli/commerce/audit/audit_commerce_payment_architecture.php --strict

HELP;
    exit(0);
}

cli_heading('Commerce payment architecture certification');

try {
    $registry = CommercePaymentProviderRegistryFactory::create();
    $currencyregistry = new CommerceCurrencyRegistry();

    $certification = (
        new CommercePaymentArchitectureCertificationService(
            $registry,
            $currencyregistry
        )
    )->certify();

    $inspector = new CommercePaymentArchitectureInspector(
        $registry
    );
} catch (\Throwable $exception) {
    cli_error(
        'Payment architecture could not be loaded: '
        . $exception->getMessage()
    );
}

foreach ($inspector->providers() as $provider) {
    cli_writeln(
        sprintf(
            '[PROVIDER] %s | available=%s | methods=%s | currencies=%s | refunds=%s | refund-history=%s',
            (string)$provider['key'],
            !empty($provider['available']) ? 'yes' : 'no',
            implode(',', (array)$provider['paymentmethods']),
            implode(',', (array)$provider['currencies']),
            !empty($provider['refundcertified']) ? 'certified' : 'no',
            !empty($provider['refundhistorycontract']) ? 'yes' : 'no'
        )
    );
}

foreach ($certification['errors'] as $issue) {
    cli_writeln(
        '[ERROR] '
        . $issue['code']
        . ': '
        . $issue['message']
    );
}

foreach ($certification['warnings'] as $issue) {
    cli_writeln(
        '[WARN] '
        . $issue['code']
        . ': '
        . $issue['message']
    );
}

cli_writeln('');
cli_writeln(
    'Errors: ' . count($certification['errors'])
);
cli_writeln(
    'Warnings: ' . count($certification['warnings'])
);
cli_writeln(
    $certification['certified']
        ? 'Architecture certification: PASS'
        : 'Architecture certification: FAIL'
);

if (
    $certification['errors'] !== []
    || (
        !empty($options['strict'])
        && $certification['warnings'] !== []
    )
) {
    exit(1);
}

exit(0);
