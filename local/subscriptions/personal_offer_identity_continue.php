<?php

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

subscription_config::guard_public_access();
require_sesskey();

$token = trim((string)required_param('token', PARAM_RAW_TRIMMED));
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$destination = strtolower(optional_param('destination', '', PARAM_ALPHA));
$anchor = strtolower(optional_param('anchor', '', PARAM_ALPHANUMEXT));

if (!in_array($currency, ['', 'EUR', 'RUB'], true)
    || !in_array($destination, ['', 'checkout'], true)
    || !in_array($anchor, ['', 'showroom-offers'], true)) {
    throw new invalid_parameter_exception('Unsupported Personal Offer continuation parameters.');
}

$params = ['token' => $token];
if ($currency !== '') { $params['currency'] = $currency; }
if ($destination !== '') { $params['destination'] = $destination; }
if ($anchor !== '') { $params['anchor'] = $anchor; }
$returnurl = new moodle_url('/local/subscriptions/offer.php', $params);

if (isloggedin() && !isguestuser()) {
    require_logout();
}
redirect($returnurl);
