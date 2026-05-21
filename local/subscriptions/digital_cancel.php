<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$pid = optional_param('pid', 0, PARAM_INT);
$uilang = optional_param('uilang', '', PARAM_ALPHANUMEXT);
$langparam = optional_param('lang', '', PARAM_ALPHANUMEXT);
$error = optional_param('error', '', PARAM_ALPHANUMEXT);

$uilang = strtolower(substr($uilang, 0, 2));

if (!in_array($uilang, ['fr', 'en', 'ru'], true)) {
    $uilang = current_language();
}

if ($uilang !== '' && strtolower(substr($langparam, 0, 2)) !== $uilang) {
    redirect(new moodle_url('/local/subscriptions/digital_cancel.php', [
        'pid' => $pid,
        'uilang' => $uilang,
        'lang' => $uilang,
    ]));
}

$pr = null;
$product = null;
$retryurl = UrlFactory::digital_catalog();

if ($pid) {
    $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', IGNORE_MISSING);

    if ($pr) {
        if (!empty($pr->payment_link)) {
            $retryurl = $pr->payment_link;
        }

        $product = product_manager::get_product_by_id((int)$pr->productid, false);

        if ($product) {
            $translation = product_manager::get_product_translation(
                (int)$product->id,
                $pr->buyer_lang ?? current_language()
            );

            $product->localized_title = $translation ? $translation->title : $product->name;

            if (empty($pr->payment_link) && !empty($product->slug)) {
                $retryurl = UrlFactory::digital_product($product->slug);
            }
        }
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/digital_cancel.php', ['pid' => $pid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('digital_cancel_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_cancel_title', 'local_subscriptions'));

echo $OUTPUT->header();

echo html_writer::start_div('container my-5');
echo html_writer::start_div('card shadow-sm border-0 mx-auto', [
    'style' => 'max-width:850px;',
]);
echo html_writer::start_div('card-body p-4 p-md-5 text-center');

if ($error === 'gateway_timeout') {
    echo $OUTPUT->notification(
        get_string('digital_cancel_gateway_timeout', 'local_subscriptions'),
        'warning'
    );
}

echo html_writer::tag('div', '⚠️', [
    'style' => 'font-size:3rem;',
]);

echo html_writer::tag('h1', get_string('digital_cancel_heading', 'local_subscriptions'), [
    'class' => 'mt-3 mb-3',
]);

echo html_writer::tag('p', get_string('digital_cancel_intro', 'local_subscriptions'), [
    'class' => 'lead mb-4',
]);

if ($product) {
    echo html_writer::start_div('border rounded-3 p-4 text-start my-4');

    echo html_writer::tag('h2', get_string('digital_success_summary_title', 'local_subscriptions'), [
        'class' => 'h4 mb-3',
    ]);

    echo html_writer::tag(
        'p',
        html_writer::tag('strong', get_string('digital_success_product', 'local_subscriptions') . ' : ') .
        format_text($product->localized_title, FORMAT_HTML, ['para' => false])
    );

    if ($pr) {
        echo html_writer::tag(
            'p',
            html_writer::tag('strong', get_string('digital_success_email', 'local_subscriptions') . ' : ') .
            s($pr->email)
        );
    }

    echo html_writer::end_div();
}

echo html_writer::start_div('d-flex flex-column flex-md-row gap-2 justify-content-center my-4');

echo html_writer::link(
    $retryurl,
    get_string('digital_cancel_retry', 'local_subscriptions'),
    ['class' => 'btn btn-primary btn-lg px-4']
);

echo html_writer::link(
    UrlFactory::digital_catalog(),
    get_string('digital_success_back_to_shop', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary btn-lg px-4']
);

echo html_writer::end_div();

echo html_writer::start_div('border rounded-3 p-4 mt-4 bg-light text-start');

echo html_writer::tag('p', get_string('digital_cancel_vpn_hint', 'local_subscriptions'), [
    'class' => 'mb-3',
]);

$supportlink = html_writer::link(
    'mailto:support@campusfr.fr',
    'support@campusfr.fr'
);

echo html_writer::tag(
    'p',
    get_string('digital_cancel_support_text', 'local_subscriptions', $supportlink),
    ['class' => 'mb-0']
);

echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();