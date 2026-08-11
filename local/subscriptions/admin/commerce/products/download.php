<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$version = optional_param('version', 'main', PARAM_ALPHA);
if (!in_array($version, ['main', 'mobile'], true)) {
    throw new moodle_exception('invalidparameter');
}

$details = (new CommerceCatalogReadRepository($DB))->find_by_sku($sku);
if ($details === null || $details->get_summary()->get_type() !== 'digital_download') {
    throw new moodle_exception('commerce_catalog_product_not_found', 'local_subscriptions');
}


$nativefiles = new CommerceCatalogDigitalFileManager(context_system::instance());
$role = $version === 'mobile'
    ? CommerceCatalogDigitalFileManager::ROLE_MOBILE
    : CommerceCatalogDigitalFileManager::ROLE_DESKTOP;
$nativefile = $nativefiles->get_file((int)$details->get_summary()->get_id(), $role);
if ($nativefile instanceof stored_file) {
    send_stored_file($nativefile, 0, 0, true, ['filename' => $nativefile->get_filename()]);
}

$digitalid = 0;
foreach ($details->get_legacy_references() as $reference) {
    if ($reference['table'] === 'subscription_digital_product') {
        $digitalid = (int)$reference['id'];
        break;
    }
}
if ($digitalid <= 0) {
    throw new moodle_exception('commerce_digital_file_unavailable', 'local_subscriptions');
}

$product = $DB->get_record('subscription_digital_product', ['id' => $digitalid], '*', MUST_EXIST);
$filename = $version === 'mobile' ? (string)($product->mobile_filename ?? '') : (string)$product->filename;
if ($filename === '') {
    throw new moodle_exception('commerce_digital_file_unavailable', 'local_subscriptions');
}
$filepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/' . basename($filename);
if (!is_readable($filepath)) {
    throw new moodle_exception('digital_download_file_missing', 'local_subscriptions');
}

send_file($filepath, basename($filename), 0, 0, false, true, 'application/pdf');
