<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\payment\certification\CommercePaymentMethodsCertificationService;
use local_subscriptions\commerce\payment\policy\CommercePaymentPresentationPolicy;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'strict' => false],
    ['h' => 'help', 's' => 'strict']
);

if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}

if (!empty($options['help'])) {
    echo <<<HELP
Certify Commerce 7.96G payment methods and routing.

Checks:
- payment-method catalogue
- Stripe / Alfa / PayPal method capabilities
- Klarna market eligibility
- admin provider/method allow-lists

This audit is read-only and never creates or executes a payment.

Options:
-h, --help     Display this help.
-s, --strict   Return non-zero for warnings too.

HELP;
    exit(0);
}

cli_heading('Commerce 7.96G payment methods and routing certification');

$result = (
    new CommercePaymentMethodsCertificationService(
        CommercePaymentProviderRegistryFactory::create(),
        new CommercePaymentPresentationPolicy()
    )
)->certify();

foreach ($result->checks as $key => $value) {
    if (is_bool($value)) {
        $value = $value ? 'yes' : 'no';
    } else if (is_array($value)) {
        $value = implode(',', $value);
    }

    cli_writeln('[CHECK] ' . $key . '=' . (string)$value);
}

foreach ($result->errors as $error) {
    cli_writeln('[ERROR] ' . $error);
}

foreach ($result->warnings as $warning) {
    cli_writeln('[WARN] ' . $warning);
}

cli_writeln('');
cli_writeln('Errors: ' . count($result->errors));
cli_writeln('Warnings: ' . count($result->warnings));
cli_writeln(
    $result->certified
        ? 'Payment methods/routing certification: PASS'
        : 'Payment methods/routing certification: FAIL'
);

exit(
    !$result->certified
    || (!empty($options['strict']) && $result->warnings !== [])
        ? 1
        : 0
);
