<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;

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
    && $pr->payment_provider === \local_subscriptions\payment\Provider::STRIPE
    && !in_array($pr->status, [
        \local_subscriptions\constants\Status::PAID,
        \local_subscriptions\constants\Status::COMPLETED,
    ], true)
) {
    $event = new \local_subscriptions\payment\dto\InternalEvent('checkout_completed', [
        'payment_request_id' => (string)$pr->id,
        'currency' => $pr->currency,
        'amount_minor' => (int)$pr->amount_minor,
        'meta' => [
            'provider' => \local_subscriptions\payment\Provider::STRIPE,
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
    && $pr->payment_provider === \local_subscriptions\payment\Provider::ALFA
    && !in_array($pr->status, [
        \local_subscriptions\constants\Status::PAID,
        \local_subscriptions\constants\Status::COMPLETED,
    ], true)
) {
    $gateway = \local_subscriptions\payment\PaymentGatewayFactory::for(
        \local_subscriptions\payment\Provider::ALFA
    );

    $event = $gateway->parse_webhook(json_encode([
        'orderId' => $orderid,
        'orderNumber' => 'digital-' . $pid . '-1',
        'payment_context' => 'digital_product',
        'payment_request_table' => product_manager::TABLE_PAYMENT_REQUEST,
    ], JSON_UNESCAPED_UNICODE), []);

    $event->meta['payment_context'] = 'digital_product';
    $event->meta['payment_request_table'] = product_manager::TABLE_PAYMENT_REQUEST;
    $event->meta['provider'] = \local_subscriptions\payment\Provider::ALFA;
    $event->meta['orderId'] = $orderid;

    \local_subscriptions\digital\digital_payment_service::on_checkout_completed($event);

    $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', MUST_EXIST);
}

$product = product_manager::get_product_by_id((int)$pr->productid, false);
$translation = product_manager::get_product_translation(
    (int)$product->id,
    $pr->buyer_lang ?? current_language()
);

if ($translation) {
    $product->localized_title = $translation->title;
}

if (!$product) {
    throw new moodle_exception('invalidrecord', 'error');
}

$ispaid = in_array($pr->status, [
    \local_subscriptions\constants\Status::PAID,
    \local_subscriptions\constants\Status::COMPLETED,
], true);

$PAGE->set_url(new moodle_url('/local/subscriptions/digital_success.php', ['pid' => $pid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('digital_success_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_success_title', 'local_subscriptions'));

echo $OUTPUT->header();

echo html_writer::start_div('container my-5');
echo html_writer::start_div('row justify-content-center');
echo html_writer::start_div('col-lg-8');

echo html_writer::start_div('card shadow-sm border-0');
echo html_writer::start_div('card-body p-4 p-md-5 text-center');

if ($preview) {
    echo $OUTPUT->notification(
        get_string('digital_success_preview', 'local_subscriptions'),
        'info'
    );
}

if ($ispaid && !empty($pr->download_token)) {
    echo html_writer::tag('div', '✅', [
        'style' => 'font-size: 3rem;',
        'class' => 'mb-3',
    ]);

    echo html_writer::tag('h1', get_string('digital_success_paid_heading', 'local_subscriptions'), [
        'class' => 'mb-3',
    ]);

    echo html_writer::tag('p', get_string('digital_success_paid_intro', 'local_subscriptions'), [
        'class' => 'lead mb-4',
    ]);
} else {
    echo html_writer::tag('div', '⏳', [
        'style' => 'font-size: 3rem;',
        'class' => 'mb-3',
    ]);

    echo html_writer::tag('h1', get_string('digital_success_pending_heading', 'local_subscriptions'), [
        'class' => 'mb-3',
    ]);

    echo html_writer::tag('p', get_string('digital_success_payment_pending', 'local_subscriptions'), [
        'class' => 'lead mb-4',
    ]);
}

echo html_writer::start_div('border rounded p-3 p-md-4 text-start mb-4');

echo html_writer::tag('h2', get_string('digital_success_summary_title', 'local_subscriptions'), [
    'class' => 'h4 mb-3',
]);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_product', 'local_subscriptions') . ' : ') .
    format_string($product->localized_title),
    ['class' => 'mb-2']
);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_amount', 'local_subscriptions') . ' : ') .
    number_format((float)$pr->price, 2, ',', ' ') . ' ' . s($pr->currency),
    ['class' => 'mb-2']
);

echo html_writer::tag(
    'p',
    html_writer::tag('strong', get_string('digital_success_email', 'local_subscriptions') . ' : ') .
    s($pr->email),
    ['class' => 'mb-0']
);

echo html_writer::end_div();

if ($ispaid && !empty($pr->download_token)) {
    echo html_writer::start_div('d-flex flex-column flex-md-row gap-2 justify-content-center mb-3');

    echo html_writer::link(
        new moodle_url('/download/pdf/' . $pr->download_token),
        get_string('digital_success_download_main', 'local_subscriptions'),
        ['class' => 'btn btn-primary btn-lg px-4']
    );

    if (!empty($product->mobile_filename)) {
        echo html_writer::link(
            new moodle_url('/download/pdf/' . $pr->download_token, [
                'version' => 'mobile',
            ]),
            get_string('digital_success_download_mobile', 'local_subscriptions'),
            ['class' => 'btn btn-outline-primary btn-lg px-4']
        );
    }

    echo html_writer::end_div();

    echo html_writer::tag('p', get_string('digital_success_email_sent_hint', 'local_subscriptions'), [
        'class' => 'text-muted small mt-3 mb-0',
    ]);
} else {
    echo html_writer::tag('p', get_string('digital_success_pending_hint', 'local_subscriptions'), [
        'class' => 'text-muted small mb-0',
    ]);
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();