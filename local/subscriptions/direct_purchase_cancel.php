<?php

// Dismiss only this browser's saved Buy Now intent; never alter payment records.
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\currency\Currency;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    throw new moodle_exception('invalidparameter');
}
require_sesskey();

$currency = Currency::sanitize(required_param('currency', PARAM_ALPHA));
$sku = strtoupper(trim(required_param('sku', PARAM_RAW_TRIMMED)));
$priceid = required_param('priceid', PARAM_INT);
$storedat = required_param('storedat', PARAM_INT);
$cartuuid = strtolower(trim(optional_param('cartuuid', '', PARAM_ALPHANUMEXT)));

$current = CommerceDirectPurchaseSession::current_any_currency();
if (
    $current !== null
    && $current['currency'] === $currency
    && $current['sku'] === $sku
    && (int)$current['priceid'] === $priceid
    && (int)$current['storedat'] === $storedat
    && hash_equals((string)($current['cartuuid'] ?? ''), $cartuuid)
) {
    // No database writes: the order/payment history is deliberately preserved.
    // A hold that is still active expires by its normal lease policy.
    CommerceDirectPurchaseSession::clear();
}

redirect(UrlFactory::digital_catalog(['currency' => $currency]));
