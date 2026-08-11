<?php

require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;

\local_subscriptions\subscription_config::guard_public_access();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$symbols = [
    'EUR' => '€',
    'RUB' => '₽',
    'USD' => '$',
    'AMD' => '֏',
    'GBP' => '£',
    'CHF' => 'CHF',
];

$currencies = array_map(
    static fn(string $code): array => [
        'code' => $code,
        'symbol' => $symbols[$code] ?? $code,
        'label' => $code . ' — ' . ($symbols[$code] ?? $code),
    ],
    CommerceShowroomCurrencyResolver::active_currencies($DB)
);

echo json_encode([
    'success' => true,
    'currencies' => $currencies,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
