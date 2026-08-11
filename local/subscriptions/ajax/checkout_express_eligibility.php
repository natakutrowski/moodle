<?php

// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\express\CommerceCheckoutExpressService;

header('Content-Type: application/json; charset=utf-8');

try {
    require_sesskey();

    $currency = strtoupper(required_param('currency', PARAM_ALPHA));
    $userid = isloggedin() && !isguestuser() ? (int)$USER->id : 0;
    $sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
    $priceid = optional_param('priceid', 0, PARAM_INT);
    $quantity = optional_param('quantity', 1, PARAM_INT);
    $operation = strtolower(optional_param('operation', '', PARAM_ALPHA));

    $service = new CommerceCheckoutExpressService();
    $reason = $sku !== ''
        ? $service->direct_purchase_ineligibility_reason(
            $userid,
            $currency,
            $sku,
            $priceid,
            $quantity,
            $operation
        )
        : $service->ineligibility_reason($userid, $currency);

    echo json_encode([
        'success' => true,
        'eligible' => $reason === '',
        'reason' => $reason,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'eligible' => false,
        'reason' => 'eligibility_check_failed',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
