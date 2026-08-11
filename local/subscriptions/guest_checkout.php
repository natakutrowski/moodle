<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\flow\CommercePurchaseFlow;
use local_subscriptions\support\Region;

\local_subscriptions\subscription_config::guard_public_access();

$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    $currency = in_array(Region::detect_country(), ['RU', 'BY'], true) ? 'RUB' : 'EUR';
}

$params = [
    'currency' => $currency,
    'flow' => CommercePurchaseFlow::normalise(optional_param('flow', CommercePurchaseFlow::CART, PARAM_ALPHA)),
];
foreach (['source', 'showroom', 'showroomoffer'] as $key) {
    $value = strtolower(optional_param($key, '', PARAM_ALPHANUMEXT));
    if ($value !== '') {
        $params[$key] = $value;
    }
}

redirect(new moodle_url('/local/subscriptions/commerce_checkout.php', $params));
