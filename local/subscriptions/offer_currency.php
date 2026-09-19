<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;

\local_subscriptions\subscription_config::guard_public_access();

global $SESSION;

$currency = strtoupper(required_param('currency', PARAM_ALPHA));
if (!(new CommerceCurrencyRegistry())->is_enabled($currency)) {
    throw new moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
}

$token = trim((string)($SESSION->local_subscriptions_personal_offer_token ?? ''));
if ($token === '') {
    throw new moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
}

redirect(new moodle_url('/local/subscriptions/offer.php', [
    'token' => $token,
    'currency' => $currency,
    'destination' => 'checkout',
]));
