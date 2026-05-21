<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUMEXT);

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

$filename = '';
$basepath = '';
$mimetype = '';

switch ($type) {
    case 'main':
        $filename = $product->filename ?? '';
        $basepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/';
        $mimetype = 'application/pdf';
        break;

    case 'mobile':
        $filename = $product->mobile_filename ?? '';
        $basepath = $CFG->dataroot . '/local_subscriptions/private_pdfs/';
        $mimetype = 'application/pdf';
        break;

    case 'cover':
        $filename = $product->coverimage ?? '';
        $basepath = $CFG->dirroot . '/local/subscriptions/pix/cover/';
        $mimetype = '';
        break;

    default:
        throw new moodle_exception('invalidrequest');
}

if (empty($filename)) {
    throw new moodle_exception('filenotfound');
}

$filepath = $basepath . $filename;

if (!is_readable($filepath)) {
    throw new moodle_exception('filenotfound');
}

send_file(
    $filepath,
    $filename,
    0,
    0,
    false,
    true,
    $mimetype
);