<?php

declare(strict_types=1);

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\commerce\presentation\CommercePresentationContext;
use local_subscriptions\commerce\presentation\CommerceVocabulary;

[$options] = cli_get_params(
    ['json' => false, 'strict' => false],
    ['j' => 'json', 's' => 'strict']
);

$families = [
    'product_type' => [
        'method' => 'product_type',
        'values' => ['course_access', 'digital_download', 'bundle', 'service'],
    ],
    'product_status' => [
        'method' => 'product_status',
        'values' => ['active', 'draft', 'inactive', 'archived'],
    ],
    'purchase_status' => [
        'method' => 'purchase_status',
        'values' => [
            'draft', 'created', 'prepared', 'payment_pending', 'authorized', 'captured', 'paid',
            'fulfillment_pending', 'fulfilled', 'completed', 'active', 'expired', 'replaced',
            'cancelled', 'failed', 'refunded', 'unknown',
        ],
    ],
    'payment_status' => [
        'method' => 'payment_status',
        'values' => [
            'created', 'requires_action', 'pending', 'authorized', 'captured', 'paid', 'succeeded',
            'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded', 'unknown',
        ],
    ],
    'fulfillment_status' => [
        'method' => 'fulfillment_status',
        'values' => ['pending', 'processing', 'fulfilled', 'completed', 'failed', 'cancelled', 'unknown'],
    ],
    'access_type' => [
        'method' => 'access_type',
        'values' => ['course', 'digital_product', 'subscription', 'bundle'],
    ],
];

$languages = ['fr', 'en', 'ru'];
$visiblecontexts = [CommercePresentationContext::CLIENT, CommercePresentationContext::CRM];
$stringmanager = get_string_manager();
$checks = [];
$errors = [];
$totalkeys = 0;

$translation_exists = static function (string $key, string $language) use ($stringmanager): bool {
    if (!$stringmanager->string_exists($key, 'local_subscriptions')) {
        return false;
    }

    // Use the string manager API: its fourth argument is the language code.
    // Do not use core get_string() for this check: its fourth argument is a deprecated
    // extra-locations parameter, not a language selector.
    $translation = $stringmanager->get_string($key, 'local_subscriptions', null, $language);
    return trim($translation) !== '' && $translation !== '[[' . $key . ']]';
};

foreach ($families as $family => $definition) {
    $familyerrors = 0;

    foreach ($definition['values'] as $value) {
        foreach ($visiblecontexts as $context) {
            $key = 'commerce_vocabulary_' . $family . '_' . $context . '_' . $value;
            $totalkeys++;

            foreach ($languages as $language) {
                if (!$translation_exists($key, $language)) {
                    $errors[] = 'Missing or empty ' . $language . ' string: ' . $key;
                    $familyerrors++;
                }
            }

            $label = CommerceVocabulary::{$definition['method']}($value, $context);

            if ($label->diagnostic_reference($context) !== null) {
                $errors[] = 'Technical reference exposed in ' . $context . ': ' . $family . ':' . $value;
                $familyerrors++;
            }

            if (trim($label->label()) === '' || $label->label() === $value) {
                $errors[] = 'Raw or empty label exposed in ' . $context . ': ' . $family . ':' . $value;
                $familyerrors++;
            }
        }
    }

    $unknownkey = 'commerce_vocabulary_' . $family . '_unknown';
    $totalkeys++;

    foreach ($languages as $language) {
        if (!$translation_exists($unknownkey, $language)) {
            $errors[] = 'Missing or empty ' . $language . ' fallback string: ' . $unknownkey;
            $familyerrors++;
        }
    }

    $unknown = CommerceVocabulary::{$definition['method']}(
        'internal_unknown_value',
        CommercePresentationContext::CLIENT
    );

    if (str_contains($unknown->label(), 'internal_unknown_value')) {
        $errors[] = 'Raw unknown value exposed by family: ' . $family;
        $familyerrors++;
    }

    if ($unknown->diagnostic_reference(CommercePresentationContext::CLIENT) !== null) {
        $errors[] = 'Unknown technical reference exposed to client by family: ' . $family;
        $familyerrors++;
    }

    if ($unknown->diagnostic_reference(CommercePresentationContext::DIAGNOSTIC) === null) {
        $errors[] = 'Unknown diagnostic reference missing for family: ' . $family;
        $familyerrors++;
    }

    $checks[] = [
        'family' => $family,
        'values' => count($definition['values']),
        'passed' => $familyerrors === 0,
    ];
}

$passed = $errors === [];
$report = [
    'phase' => '7.95B1',
    'passed' => $passed,
    'families' => count($families),
    'known_values' => array_sum(array_map(static fn(array $item): int => count($item['values']), $families)),
    'translation_keys' => $totalkeys,
    'languages' => $languages,
    'contexts' => CommercePresentationContext::all(),
    'checks' => $checks,
    'errors' => $errors,
];

if ($options['json']) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit($passed ? 0 : 1);
}

echo "== 7.95B1 Commerce vocabulary contract ==\n\n";

foreach ($checks as $check) {
    echo sprintf(
        "%-28s %s (%d values)\n",
        $check['family'],
        $check['passed'] ? 'OK' : 'FAIL',
        $check['values']
    );
}

echo sprintf("\nTranslations                %s (%s)\n", $passed ? 'OK' : 'FAIL', implode(', ', $languages));
echo sprintf("Presentation contexts       %s\n", $passed ? 'OK' : 'FAIL');

if ($errors !== []) {
    echo "\nFindings:\n";
    foreach ($errors as $error) {
        echo '[FAIL] ' . $error . "\n";
    }
}

echo "\n" . ($passed ? '[CERTIFIED]' : '[NOT CERTIFIED]') . "\n";

if (!$passed && $options['strict']) {
    cli_error('Commerce vocabulary contract is not certified.');
}

exit($passed ? 0 : 1);
