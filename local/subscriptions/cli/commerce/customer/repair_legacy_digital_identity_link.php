<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\identity\CommerceLegacyDigitalIdentityLinkService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

[$options, $unrecognized] = cli_get_params(
    [
        'email' => null,
        'userid' => null,
        'execute' => false,
        'help' => false,
    ],
    ['h' => 'help']
);

if ($unrecognized) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognized));
}

if ($options['help']) {
    echo "Dry-run:\n";
    echo "php local/subscriptions/cli/commerce/customer/repair_legacy_digital_identity_link.php "
        . "--email=buyer@example.com --userid=847\n\n";
    echo "Execute:\n";
    echo "php local/subscriptions/cli/commerce/customer/repair_legacy_digital_identity_link.php "
        . "--email=buyer@example.com --userid=847 --execute\n";
    exit(0);
}

$email = trim((string)$options['email']);
$userid = (int)$options['userid'];

if ($email === '' || $userid <= 1) {
    cli_error('--email and a Moodle --userid > 1 are required.');
}

$service = new CommerceLegacyDigitalIdentityLinkService(
    $DB,
    new CommerceCustomerIdentitySimilarityService($DB),
    new CommerceCustomerIdentityReconciliationService($DB)
);

$preview = $service->preview($email, $userid);

echo "Legacy email: {$preview->legacyemail}\n";
echo "Target Moodle user: #{$preview->targetuserid} {$preview->targetemail}\n";
echo "Legacy purchases: {$preview->legacypurchases}\n";
echo "Native projections: {$preview->nativepurchases}\n";
echo "Native projections already linked: {$preview->nativepurchaseslinked}\n";
echo "Similarity score: {$preview->similarityscore}%\n";

if (!$options['execute']) {
    echo "[DRY-RUN] No data changed.\n";
    exit(0);
}

$result = $service->execute(
    $email,
    $userid,
    (int)get_admin()->id
);

echo "[OK] Link repaired.\n";
echo "Native projections linked: {$result->nativepurchaseslinked}/{$result->nativepurchases}\n";
