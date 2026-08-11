<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomCurrencyResolver;
use local_subscriptions\commerce\showroom\CommerceShowroomProductResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver;

\local_subscriptions\subscription_config::guard_public_access();

$PAGE->set_context(context_system::instance());
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $showroomkey = required_param('showroom', PARAM_ALPHANUMEXT);
    $currency = strtoupper(required_param('currency', PARAM_ALPHA));
    $definition = (new CommerceShowroomPublishedDefinitionResolver($DB))->require($showroomkey);
    $available = CommerceShowroomCurrencyResolver::active_currencies($DB);
    if (!in_array($currency, $available, true)) {
        throw new invalid_parameter_exception('Unsupported currency.');
    }

    $offers = CommerceShowroomProductResolver::create($DB)->resolve(
        $definition,
        current_language(),
        $currency
    );

    $payload = [];
    foreach ($offers as $offer) {
        $payload[(string)$offer['role']] = [
            'priceformatted' => (string)($offer['priceformatted'] ?? ''),
            'compareformatted' => (string)($offer['compareformatted'] ?? ''),
            'hascompareprice' => !empty($offer['hascompareprice']),
            'discountlabel' => (string)($offer['discountlabel'] ?? ''),
            'haspromotion' => !empty($offer['haspromotion']),
            'priceid' => (int)($offer['priceid'] ?? 0),
            'currency' => $currency,
            'canbuy' => !empty($offer['canbuy']),
            'available' => !empty($offer['available']),
            'bundleblocked' => !empty($offer['bundleblocked']),
            'bundleblockedmessage' => (string)($offer['bundleblockedmessage'] ?? ''),
        ];
    }

    $SESSION->local_subscriptions_showroom_currency = $currency;
    $SESSION->local_subscriptions_storefront_currency = $currency;

    $invoiceprofile = (new CommerceInvoiceProfileResolver())->resolve($currency, null);
    $legalprofile = [];
    foreach ([
        'name',
        'address',
        'legal',
        'email',
        'phone',
        'website',
        'taxnotice',
        'footer',
    ] as $field) {
        $legalprofile[$field] = trim((string)($invoiceprofile[$field] ?? ''));
    }

    echo json_encode([
        'ok' => true,
        'currency' => $currency,
        'offers' => $payload,
        'legalprofile' => $legalprofile,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], JSON_THROW_ON_ERROR);
}
