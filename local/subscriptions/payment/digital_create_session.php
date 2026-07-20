<?php
require_once(dirname(__DIR__, 3) . '/config.php');

defined('MOODLE_INTERNAL') || die();

require_sesskey();

use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\PaymentFailureReporter;
use local_subscriptions\constants\Status;

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
    $result = $gateway->create_checkout_session(
        $pr,
        $options
    );
} catch (\Throwable $e) {
    $reference =
        PaymentFailureReporter::generate_reference();

    $technicalmessage =
        PaymentFailureReporter::technical_message(
            $e,
            $reference,
            'digital_checkout_create'
        );

    PaymentFailureReporter::log(
        $e,
        $reference,
        'digital_checkout_create',
        [
            'payment_request_id' =>
                (int)$pr->id,

            'provider' =>
                (string)$provider,

            'currency' =>
                (string)$pr->currency,

            'product_id' =>
                (int)$product->id,

            'slug' =>
                (string)$product->slug,
        ]
    );

    $DB->update_record(
        product_manager::TABLE_PAYMENT_REQUEST,
        (object)[
            'id' =>
                (int)$pr->id,

            'status' =>
                Status::FAILED,

            'last_error' =>
                $technicalmessage,

            'last_update' =>
                time(),
        ]
    );

    redirect(
        UrlFactory::digital_cancel(
            [
                'pid' =>
                    (int)$pr->id,

                'uilang' =>
                    $uilang,

                'error' =>
                    'gateway',

                'reference' =>
                    $reference,
            ]
        )
    );
}

$redirecturl = '';

if (is_string($result)) {
    $redirecturl = $result;
} elseif (is_array($result)) {
    $redirecturl =
        $result['redirect_url'] ??
        $result['url'] ??
        '';
} elseif (is_object($result)) {
    if (
        property_exists(
            $result,
            'redirect_url'
        )
    ) {
        $redirecturl =
            $result->redirect_url;
    } elseif (
        method_exists(
            $result,
            'getUrl'
        )
    ) {
        $redirecturl =
            $result->getUrl();
    } elseif (
        method_exists(
            $result,
            'get_redirect_url'
        )
    ) {
        $redirecturl =
            $result->get_redirect_url();
    }
}

$redirecturl =
    UrlFactory::validate_external_payment_url(
        (string)$redirecturl
    );

$update = new stdClass();
$update->id = (int)$pr->id;
$update->payment_link = $redirecturl;
$update->last_error = null;
$update->last_update = time();

$DB->update_record(
    product_manager::TABLE_PAYMENT_REQUEST,
    $update
);

redirect($redirecturl);