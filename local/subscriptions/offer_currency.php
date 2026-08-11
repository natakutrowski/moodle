<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

\local_subscriptions\subscription_config::guard_public_access();

global $SESSION;

$currency = strtoupper(required_param('currency', PARAM_ALPHA));
if (!in_array($currency, ['EUR', 'RUB'], true)) {
    throw new moodle_exception('commerce_personal_offer_currency_unavailable', 'local_subscriptions');
}

$token = trim((string)($SESSION->local_subscriptions_personal_offer_token ?? ''));
if ($token === '') {
    throw new moodle_exception('commerce_personal_offer_link_unavailable', 'local_subscriptions');
}

redirect(new moodle_url('/local/subscriptions/offer.php', [
    'token' => $token,
    'currency' => $currency,
]));
