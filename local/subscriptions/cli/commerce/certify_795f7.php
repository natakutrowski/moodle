<?php

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\certification\CommerceCheckoutCertificationAuditor;
use local_subscriptions\commerce\certification\CommerceOwnershipCertificationAuditor;
use local_subscriptions\commerce\certification\CommercePricingCertificationAuditor;
use local_subscriptions\commerce\certification\CommerceStorefrontBaselineAuditor;
use local_subscriptions\commerce\certification\CommerceStorefrontUxCertificationAuditor;

[$options, $unrecognized] = cli_get_params(['json' => false, 'help' => false], ['h' => 'help']);
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['help']) {
    echo "Commerce 7.95F7 global certification\n\nOptions: --json\n";
    exit(0);
}

$baseline = (new CommerceStorefrontBaselineAuditor($DB))->audit()->to_array();
$pricing = (new CommercePricingCertificationAuditor($DB))->audit()->to_array();
$checkout = (new CommerceCheckoutCertificationAuditor($DB))->audit()->to_array();
$ownership = (new CommerceOwnershipCertificationAuditor($DB))->audit()->to_array();
$ux = (new CommerceStorefrontUxCertificationAuditor(__DIR__ . '/../..'))->audit()->to_array();

$phases = [
    'F7A Baseline' => [
        'certifiable' => (bool)$baseline['certifiablebaseline'],
        'summary' => [
            'blocking' => (int)$baseline['summary']['blocking'],
            'important' => (int)$baseline['summary']['important'],
            'cosmetic' => (int)$baseline['summary']['cosmetic'],
        ],
    ],
    'F7B Pricing' => [
        'certifiable' => (bool)$pricing['certifiable'],
        'summary' => $pricing['summary'],
    ],
    'F7C Checkout' => [
        'certifiable' => (bool)$checkout['certifiable'],
        'summary' => $checkout['summary'],
    ],
    'F7D Ownership' => [
        'certifiable' => (bool)$ownership['certifiable'],
        'summary' => $ownership['summary'],
    ],
    'F7F UX' => [
        'certifiable' => (bool)$ux['certifiable'],
        'summary' => $ux['summary'],
    ],
];

$certified = true;
foreach ($phases as $phase) {
    $certified = $certified && !empty($phase['certifiable']);
}

$payload = [
    'phase' => '7.95F7G',
    'generatedat' => time(),
    'certified' => $certified,
    'phases' => $phases,
];

if ($options['json']) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($certified ? 0 : 1);
}

echo "# Commerce 7.95F7 — Global certification\n\n";
foreach ($phases as $label => $phase) {
    echo sprintf(
        "- [%s] %s — blocking=%d, important=%d, cosmetic=%d\n",
        !empty($phase['certifiable']) ? 'PASS' : 'FAIL',
        $label,
        (int)$phase['summary']['blocking'],
        (int)$phase['summary']['important'],
        (int)$phase['summary']['cosmetic']
    );
}

echo "\n========================================\n";
echo $certified ? "STOREFRONT CERTIFIED\n" : "STOREFRONT NOT CERTIFIED\n";
echo "========================================\n";
exit($certified ? 0 : 1);
