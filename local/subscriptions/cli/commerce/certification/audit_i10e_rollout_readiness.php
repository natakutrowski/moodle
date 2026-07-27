<?php

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\dualwrite\CommerceDualWriteFeatureToggle;

[$options] = cli_get_params(
    [
        'strict' => false,
        'help' => false,
    ],
    [
        's' => 'strict',
        'h' => 'help',
    ]
);

if (!empty($options['help'])) {
    cli_writeln('Audit I10E rollout readiness.');
    cli_writeln('');
    cli_writeln('Options:');
    cli_writeln('  --strict, -s   Exit with status 2 when configuration is inconsistent.');
    cli_writeln('  --help, -h     Display this help.');
    exit(0);
}

$toggle = new CommerceDualWriteFeatureToggle();
$canonical = $toggle->canonical_enabled();
$legacyalias = $toggle->legacy_alias_enabled();
$mismatch = $toggle->has_configuration_mismatch();

cli_writeln('== I10E rollout readiness audit ==');
cli_writeln('');
cli_writeln('Runtime dual-write configuration');
cli_writeln('  canonical flag   ' . ($canonical ? 'enabled' : 'disabled'));
cli_writeln('  legacy alias     ' . ($legacyalias ? 'enabled' : 'disabled'));
cli_writeln('  effective state  ' . ($toggle->is_enabled() ? 'enabled' : 'disabled'));
cli_writeln('  strict mode      ' . ($toggle->is_strict() ? 'enabled' : 'disabled'));
cli_writeln('');

$integrations = [
    'digital checkout persistence' => 'classes/commerce/checkout/CommerceCheckoutPersistenceService.php',
    'digital post-payment' => 'classes/commerce/postpayment/DigitalPostPaymentProcessor.php',
    'subscription post-payment' => 'classes/commerce/postpayment/SubscriptionPostPaymentProcessor.php',
    'digital fulfillment emails' => 'classes/commerce/fulfillment/postaction/DigitalEmailPostFulfillmentAction.php',
    'legacy digital payment service' => 'classes/digital/digital_payment_service.php',
    'subscription payment service' => 'classes/domain/PaymentService.php',
    'paid-payment repair job' => 'classes/commerce/task/job/PaidPaymentRequestRepairJob.php',
];

$root = realpath(__DIR__ . '/..');
$missing = [];

cli_writeln('Critical runtime integration points');
foreach ($integrations as $label => $relativepath) {
    $path = $root . DIRECTORY_SEPARATOR . $relativepath;
    $contents = is_file($path) ? (file_get_contents($path) ?: '') : '';
    $ok = str_contains($contents, 'CommerceDualWriteBridge');

    if (!$ok) {
        $missing[] = $label;
    }

    cli_writeln('  ' . str_pad($label, 38) . ($ok ? '[OK]' : '[MISSING]'));
}

cli_writeln('');

if ($mismatch) {
    cli_writeln('[WARN] Canonical and historical dual-write flags differ.');
    cli_writeln('       I10E accepts both temporarily, but use commerce_native_dual_write_enabled from now on.');
}

if ($missing !== []) {
    cli_writeln('[ERROR] One or more critical runtime integration points are missing.');
    exit(!empty($options['strict']) ? 2 : 1);
}

if ($mismatch && !empty($options['strict'])) {
    exit(2);
}

cli_writeln('[OK] I10E rollout prerequisites are present.');
exit(0);
