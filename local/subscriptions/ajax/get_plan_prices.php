<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB;

header('Content-Type: application/json; charset=utf-8');

$planid = required_param('planid', PARAM_INT);
$preferredcurrency = optional_param('currency', 'EUR', PARAM_ALPHA);
$preferredcurrency = strtoupper($preferredcurrency);

$columns = $DB->get_columns('subscription_plan_price');
$planfield = array_key_exists('planid', $columns) ? 'planid' : 'plan_id';

$prices = $DB->get_records('subscription_plan_price', [$planfield => $planid], 'currency ASC');

$options = [];

foreach ($prices as $price) {
    $currency = strtoupper((string)$price->currency);
    $amount = number_format((float)$price->price, 2, '.', '');

    $options[] = [
        'value' => $amount . '|' . $currency,
        'label' => $amount . ' ' . $currency,
        'currency' => $currency,
        'selected' => $currency === $preferredcurrency,
    ];
}

echo json_encode(['prices' => $options], JSON_UNESCAPED_UNICODE);
exit;