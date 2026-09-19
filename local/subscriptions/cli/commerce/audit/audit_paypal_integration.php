<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\payment\provider\paypal\PayPalIntegrationCertificationService;
use local_subscriptions\commerce\payment\provider\paypal\PayPalOperationalHealthService;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'remote' => false,
        'strict' => false,
    ],
    [
        'h' => 'help',
        'r' => 'remote',
        's' => 'strict',
    ]
);

if ($unrecognized) {
    cli_error(
        'Unknown options: '
        . implode(', ', $unrecognized)
    );
}

if (!empty($options['help'])) {
    echo <<<HELP
Certify the Commerce 7.96 PayPal integration.

This audit checks the provider registration, PayPal method, redirect/retrieval
capabilities, refund contracts, currencies, credentials and webhook setup.
With --remote it also performs a read-only PayPal OAuth connectivity check.

It never creates, captures or refunds a payment.

Options:
-h, --help       Display this help.
-r, --remote     Verify PayPal OAuth connectivity.
-s, --strict     Return non-zero for warnings too.

Example:
php local/subscriptions/cli/commerce/audit/audit_paypal_integration.php --remote --strict

HELP;
    exit(0);
}

cli_heading('Commerce 7.96 PayPal certification');

try {
    $result =
        (
            new PayPalIntegrationCertificationService(
                CommercePaymentProviderRegistryFactory::create(),
                PayPalOperationalHealthService::create()
            )
        )->certify(
            !empty($options['remote'])
        );
} catch (\Throwable $exception) {
    cli_error(
        'PayPal certification could not run: '
        . $exception->getMessage()
    );
}

foreach ($result->checks as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } else if (is_array($value)) {
        $value = implode(',', $value);
    }

    cli_writeln(
        '[CHECK] '
        . $key
        . '='
        . (string)$value
    );
}

foreach ($result->errors as $error) {
    cli_writeln(
        '[ERROR] ' . $error
    );
}

foreach ($result->warnings as $warning) {
    cli_writeln(
        '[WARN] ' . $warning
    );
}

cli_writeln('');
cli_writeln(
    'Errors: '
    . count($result->errors)
);
cli_writeln(
    'Warnings: '
    . count($result->warnings)
);
cli_writeln(
    $result->certified
        ? 'PayPal certification: PASS'
        : 'PayPal certification: FAIL'
);

if (
    !$result->certified
    || (
        !empty($options['strict'])
        && $result->warnings !== []
    )
) {
    exit(1);
}

exit(0);
