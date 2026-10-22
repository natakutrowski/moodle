<?php

require_once(__DIR__ . '/../../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\donation\CommerceDonationIntentSession;

\local_subscriptions\subscription_config::guard_public_access();
require_sesskey();

header('Content-Type: application/json; charset=utf-8');

try {
    $sku = strtoupper(trim(required_param('sku', PARAM_RAW_TRIMMED)));
    $units = required_param('units', PARAM_INT);

    if ($sku === '') {
        throw new moodle_exception('invalidparameter');
    }
    $product = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => $sku],
        'id, sku, type',
        IGNORE_MISSING
    );
    if ($product === false || strtolower(trim((string)$product->type)) !== CommerceProductType::DONATION) {
        throw new moodle_exception('invalidparameter');
    }

    $stored = (new CommerceDonationIntentSession())->remember($sku, $units);
    echo json_encode(['ok' => true, 'units' => $stored], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['ok' => false], JSON_THROW_ON_ERROR);
}
