<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\digital\product_manager;

\local_subscriptions\subscription_config::guard_public_access();

$token = required_param('token', PARAM_ALPHANUMEXT);
$version = optional_param('version', 'main', PARAM_ALPHANUMEXT);

$version = strtolower($version);

if (!in_array($version, ['main', 'mobile'], true)) {
    $version = 'main';
}

$pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, [
    'download_token' => $token,
], '*', MUST_EXIST);

if (!in_array($pr->status, [Status::PAID, Status::COMPLETED], true)) {
    throw new moodle_exception('digital_download_not_paid', 'local_subscriptions');
}

if (!empty($pr->download_token_expires) && (int)$pr->download_token_expires < time()) {
    throw new moodle_exception('digital_download_expired', 'local_subscriptions');
}

$product = product_manager::get_product_by_id((int)$pr->productid, false);
if (!$product) {
    throw new moodle_exception('invalidrecord', 'error');
}

$filename = $product->filename;

if ($version === 'mobile') {
    if (empty($product->mobile_filename)) {
        throw new moodle_exception('digital_download_mobile_missing', 'local_subscriptions');
    }

    $filename = $product->mobile_filename;
}

$filepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . $filename;

if (!is_readable($filepath)) {
    throw new moodle_exception('digital_download_file_missing', 'local_subscriptions');
}

send_file(
    $filepath,
    $filename,
    0,
    0,
    false,
    true,
    'application/pdf'
);