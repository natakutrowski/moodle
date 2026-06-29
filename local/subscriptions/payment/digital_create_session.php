<?php
require_once(dirname(__DIR__, 3) . '/config.php');

defined('MOODLE_INTERNAL') || die();

require_sesskey();

use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(UrlFactory::digital_checkout());
$PAGE->set_pagelayout('base');

$slug = required_param('slug', PARAM_ALPHANUMEXT);
$email = trim(required_param('email', PARAM_RAW_TRIMMED));

if ($email === '' || !validate_email($email)) {
    throw new moodle_exception('digital_invalid_email', 'local_subscriptions');
}
$firstname = required_param('firstname', PARAM_NOTAGS);
$lastname = required_param('lastname', PARAM_NOTAGS);
$currency = strtoupper(required_param('currency', PARAM_ALPHANUMEXT));

$uilang = optional_param('uilang', '', PARAM_ALPHANUMEXT);

if ($uilang === '') {
    $uilang = strtolower(substr((string)($SESSION->lang ?? $USER->lang ?? $CFG->lang ?? (get_config('defaultuserlang', 'local_subscriptions') ?? 'ru')), 0, 2));
}

if (!in_array($uilang, ['fr', 'en', 'ru'], true)) {
    $uilang = 'ru';
}

$product = product_manager::get_localized_product_by_slug($slug, $uilang, true);
if (!$product) {
    throw new moodle_exception('invalidrecord', 'error');
}

$pr = product_manager::create_payment_request(
    $product,
    $email,
    $firstname,
    $lastname,
    $currency,
    $uilang
);

$provider = $pr->payment_provider;
$gateway = PaymentGatewayFactory::for($provider);

if ($provider === Provider::STRIPE) {
    $successurl = UrlFactory::digital_success([
        'pid' => $pr->id,
        'session_id' => '{CHECKOUT_SESSION_ID}',
    ])->out(false);

    $successurl = str_replace(
        '%7BCHECKOUT_SESSION_ID%7D',
        '{CHECKOUT_SESSION_ID}',
        $successurl
    );
} else {
    $successurl = UrlFactory::digital_success([
        'pid' => $pr->id,
        'uilang' => $uilang,
    ])->out(false);
}

$cancelurl = UrlFactory::digital_cancel([
    'pid' => $pr->id,
    'uilang' => $uilang,
])->out(false);

$options = [
    'mode' => 'payment',
    'use_locked_amount' => true,
    'product_name' => format_string($product->localized_title ?? $product->name),
    'success_url' => $successurl,
    'cancel_url' => $cancelurl,

    // Important pour Alfa.
    'returnurl' => $successurl,
    'failurl' => $cancelurl,
    'payment_request_table' => product_manager::TABLE_PAYMENT_REQUEST,
    'order_number' => 'digital-' . $pr->id,
    'order_number_prefix' => 'digital',

    'language' => ($uilang === 'ru') ? 'ru' : 'en',

    'metadata' => [
        'payment_context' => 'digital_product',
        'productid' => (string)$product->id,
        'slug' => (string)$product->slug,
        'uilang' => $uilang,
    ],

    'email' => $pr->email,
    'firstname' => $pr->firstname,
    'lastname' => $pr->lastname,
];

try {
    $result = $gateway->create_checkout_session($pr, $options);
} catch (\Throwable $e) {
    $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
        'id' => $pr->id,
        'status' => 'failed',
        'last_error' => '[checkout_create_failed] ' . $e->getMessage(),
        'last_update' => time(),
    ]);

    redirect(UrlFactory::digital_cancel([
        'pid' => $pr->id,
        'uilang' => $uilang,
        'error' => 'gateway_timeout',
    ]));
}

$update = new stdClass();
$update->id = $pr->id;
$update->payment_link = $result->redirect_url;
$update->last_update = time();

if (!empty($result->provider_session_id)) {
    $update->sessionid = $result->provider_session_id;
}

$DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, $update);

redirect($result->redirect_url);