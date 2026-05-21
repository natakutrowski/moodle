<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\digital\product_manager;
use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$PAGE->set_context(context_system::instance());

$pid = required_param('pid', PARAM_INT);
$preview = optional_param('preview', 0, PARAM_BOOL);
$sessionid = optional_param('session_id', '', PARAM_RAW_TRIMMED);
$orderid = optional_param('orderId', '', PARAM_RAW_TRIMMED);

$uilang = optional_param('uilang', '', PARAM_ALPHANUMEXT);
$langparam = optional_param('lang', '', PARAM_ALPHANUMEXT);

$uilang = strtolower(substr($uilang, 0, 2));

if (!in_array($uilang, ['fr', 'en', 'ru'], true)) {
    $uilang = current_language();
}

if ($uilang !== '' && strtolower(substr($langparam, 0, 2)) !== $uilang) {
    $redirectparams = [
        'pid' => $pid,
        'uilang' => $uilang,
        'lang' => $uilang,
    ];

    if ($preview) {
        $redirectparams['preview'] = 1;
    }

    if ($sessionid !== '') {
        $redirectparams['session_id'] = $sessionid;
    }

    if ($orderid !== '') {
        $redirectparams['orderId'] = $orderid;
    }

    redirect(new moodle_url('/local/subscriptions/digital_success.php', $redirectparams));
}

$pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', MUST_EXIST);

// Stripe fallback.
if (
    $sessionid !== ''
    && $pr->payment_provider === Provider::STRIPE
    && !in_array($pr->status, [Status::PAID, Status::COMPLETED], true)
) {
    $event = new InternalEvent('checkout_completed', [
        'payment_request_id' => (string)$pr->id,
        'currency' => $pr->currency,
        'amount_minor' => (int)$pr->amount_minor,
        'meta' => [
            'provider' => Provider::STRIPE,
            'session' => $sessionid,
            'payment_context' => 'digital_product',
        ],
    ]);

    \local_subscriptions\digital\digital_payment_service::on_checkout_completed($event);

    $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', MUST_EXIST);
}

// Alfa fallback.
if (
    $orderid !== ''
    && $pr->payment_provider === Provider::ALFA
    && !in_array($pr->status, [Status::PAID, Status::COMPLETED], true)
) {
    $gateway = PaymentGatewayFactory::for(Provider::ALFA);

    $event = $gateway->parse_webhook(json_encode([
        'orderId' => $orderid,
        'orderNumber' => 'digital-' . $pid . '-1',
        'payment_context' => 'digital_product',
        'payment_request_table' => product_manager::TABLE_PAYMENT_REQUEST,
    ], JSON_UNESCAPED_UNICODE), []);

    $event->meta['payment_context'] = 'digital_product';
    $event->meta['payment_request_table'] = product_manager::TABLE_PAYMENT_REQUEST;
    $event->meta['provider'] = Provider::ALFA;
    $event->meta['orderId'] = $orderid;

    \local_subscriptions\digital\digital_payment_service::on_checkout_completed($event);

    $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', MUST_EXIST);
}

$product = product_manager::get_product_by_id((int)$pr->productid, false);

if (!$product) {
    redirect(
        UrlFactory::digital_catalog(['missingproduct' => 1]),
        get_string('digital_product_not_found_redirect', 'local_subscriptions'),
        0,
        \core\output\notification::NOTIFY_WARNING
    );
}

$translation = product_manager::get_product_translation(
    (int)$product->id,
    $pr->buyer_lang ?? current_language()
);

$product->localized_title = $translation ? $translation->title : $product->name;

$ispaid = in_array($pr->status, [Status::PAID, Status::COMPLETED], true);
$hasdownload = $ispaid && !empty($pr->download_token);

$PAGE->set_url(new moodle_url('/local/subscriptions/digital_success.php', ['pid' => $pid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('digital_success_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_success_title', 'local_subscriptions'));

echo $OUTPUT->header();

echo html_writer::start_div('container my-5');
echo html_writer::start_div('card shadow-sm border-0 mx-auto', [
    'style' => 'max-width:920px;',
]);
echo html_writer::start_div('card-body p-4 p-md-5 text-center');

if ($preview) {
    echo $OUTPUT->notification(
        get_string('digital_success_preview', 'local_subscriptions'),
        'info'
    );
}

if ($hasdownload) {
    echo html_writer::tag('div', '✅', [
        'style' => 'font-size:3rem;',
    ]);

    echo html_writer::tag('h1', get_string('digital_success_thank_you', 'local_subscriptions'), [
        'class' => 'mt-3 mb-3',
    ]);

    echo html_writer::tag('p', get_string('digital_success_confirmed_intro', 'local_subscriptions'), [
        'class' => 'lead mb-4',
    ]);
} else {
    echo html_writer::tag('div', '⏳', [
        'style' => 'font-size:3rem;',
    ]);

    echo html_writer::tag('h1', get_string('digital_success_pending_title', 'local_subscriptions'), [
        'class' => 'mt-3 mb-3',
    ]);

    echo $OUTPUT->notification(
        get_string('digital_success_payment_pending_support', 'local_subscriptions'),
        'info'
    );
}

echo html_writer::start_div('border rounded-3 p-4 text-start my-4');

echo html_writer::tag('h2', get_string('digital_success_summary_title', 'local_subscriptions'), [
    'class' => 'h4 mb-4',
]);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_product', 'local_subscriptions') . ' : ') .
    format_text($product->localized_title, FORMAT_HTML, ['para' => false])
);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_amount', 'local_subscriptions') . ' : ') .
    number_format((float)$pr->price, 2, ',', ' ') . ' ' . s($pr->currency)
);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_email', 'local_subscriptions') . ' : ') .
    s($pr->email)
);

echo html_writer::end_div();

if ($hasdownload) {
    echo html_writer::start_div('row g-3 justify-content-center my-4');

    echo html_writer::start_div('col-md-6');

    echo html_writer::link(
        new moodle_url('/download/pdf/' . $pr->download_token),
        get_string('digital_success_download_main', 'local_subscriptions'),
        ['class' => 'btn btn-primary btn-lg w-100']
    );

    echo html_writer::tag('p', get_string('digital_success_main_version_hint', 'local_subscriptions'), [
        'class' => 'small text-muted mt-2 mb-0',
    ]);

    echo html_writer::end_div();

    if (!empty($product->mobile_filename)) {
        echo html_writer::start_div('col-md-6');

        echo html_writer::link(
            new moodle_url('/download/pdf/' . $pr->download_token, ['version' => 'mobile']),
            get_string('digital_success_download_mobile', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-lg w-100']
        );

        echo html_writer::tag('p', get_string('digital_success_mobile_version_hint', 'local_subscriptions'), [
            'class' => 'small text-muted mt-2 mb-0',
        ]);

        echo html_writer::end_div();
    }

    echo html_writer::end_div();

    echo html_writer::tag('p', get_string('digital_success_email_sent_notice', 'local_subscriptions'), [
        'class' => 'text-muted mt-4 mb-0',
    ]);
}

echo html_writer::start_div('border rounded-3 p-4 mt-5 bg-light');

echo html_writer::tag('h2', get_string('digital_success_support_title', 'local_subscriptions'), [
    'class' => 'h5 mb-2',
]);

$supportlink = html_writer::link(
    'mailto:support@campusfr.fr',
    'support@campusfr.fr'
);

echo html_writer::tag(
    'p',
    get_string('digital_success_support_text', 'local_subscriptions', $supportlink),
    ['class' => 'mb-3']
);

echo html_writer::link(
    UrlFactory::digital_catalog(),
    get_string('digital_success_back_to_shop', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();