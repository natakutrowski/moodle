<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\AdminDetailRenderer;
use local_subscriptions\support\DigitalPresenter;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_DIGITAL);

$id = required_param('id', PARAM_INT);

$purchase = $DB->get_record_sql("
    SELECT
        pr.*,
        p.name AS productname,
        p.slug,
        p.filename,
        p.mobile_filename,
        COALESCE(pr.userid, uemail.id) AS crmuserid
      FROM {subscription_digital_payment_request} pr
      JOIN {subscription_digital_product} p ON p.id = pr.productid
 LEFT JOIN {user} uemail
        ON " . $DB->sql_compare_text('uemail.email') . " = " . $DB->sql_compare_text('pr.email') . "
       AND uemail.deleted = 0
       AND uemail.suspended = 0
     WHERE pr.id = :id
", ['id' => $id], MUST_EXIST);

$url = new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('digital_purchase_details', 'local_subscriptions') . ' #' . $purchase->id);
$PAGE->set_heading(get_string('digital_purchase_details', 'local_subscriptions'));
$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

echo $OUTPUT->header();

echo AdminNavigation::back_button();

echo html_writer::start_div('mb-4 d-flex flex-wrap gap-2');

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchases_admin_page()),
    '← ' . get_string('digital_purchases', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchase_resend_email_admin_page(), [
        'id' => $purchase->id,
        'sesskey' => sesskey(),
    ]),
    '✉️ ' . get_string('digital_purchase_resend_email', 'local_subscriptions'),
    [
        'class' => 'btn btn-outline-primary',
        'onclick' => "return confirm('" . addslashes(get_string('digital_purchase_resend_email_confirm', 'local_subscriptions')) . "');",
    ]
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchase_extend_token_admin_page(), [
        'id' => $purchase->id,
        'sesskey' => sesskey(),
    ]),
    '⏳ ' . get_string('digital_purchase_extend_token', 'local_subscriptions'),
    [
        'class' => 'btn btn-outline-secondary',
        'onclick' => "return confirm('" . addslashes(get_string('digital_purchase_extend_token_confirm', 'local_subscriptions')) . "');",
    ]
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchase_regenerate_token_admin_page(), [
        'id' => $purchase->id,
        'sesskey' => sesskey(),
    ]),
    '🔄 ' . get_string('digital_purchase_regenerate_token', 'local_subscriptions'),
    [
        'class' => 'btn btn-outline-danger',
        'onclick' => "return confirm('" . addslashes(get_string('digital_purchase_regenerate_token_confirm', 'local_subscriptions')) . "');",
    ]
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchase_check_provider_admin_page(), [
        'id' => $purchase->id,
        'sesskey' => sesskey(),
    ]),
    '🔎 ' . get_string('digital_check_provider_now', 'local_subscriptions'),
    [
        'class' => 'btn btn-outline-primary',
        'onclick' => "return confirm('" . addslashes(get_string('digital_check_provider_now_confirm', 'local_subscriptions')) . "');",
    ]
);

echo html_writer::end_div();

$product = AdminEntityLinks::digital_product(
    (int)$purchase->productid,
    format_string($purchase->productname)
);

$buyername = trim(($purchase->firstname ?? '') . ' ' . ($purchase->lastname ?? ''));
$buyerlabel = $buyername !== '' ? s($buyername) : s($purchase->email);

if (!empty($purchase->crmuserid)) {
    $buyer = AdminEntityLinks::user((int)$purchase->crmuserid, $buyerlabel);
} else {
    $buyer = $buyerlabel;
}

$providerdata = AdminDetailRenderer::json($purchase->response_json ?? '');

$rows = [
    get_string('product', 'local_subscriptions') => $product,
    get_string('user') => $buyer,
    get_string('email') => s($purchase->email ?? '-'),

    get_string('price', 'local_subscriptions') => AdminFormatter::price($purchase->price ?? 0, $purchase->currency ?? ''),
    get_string('status', 'local_subscriptions') => DigitalPresenter::render_status_badge($purchase->status ?? ''),

    get_string('digital_payment_provider', 'local_subscriptions') => DigitalPresenter::render_provider_icon($purchase->payment_provider ?? ''),
    get_string('digital_session_id', 'local_subscriptions') => s($purchase->sessionid ?? '-'),
    get_string('digital_transaction_id', 'local_subscriptions') => s($purchase->transactionid ?? '-'),
    get_string('digital_payment_link', 'local_subscriptions') => !empty($purchase->payment_link)
        ? html_writer::link($purchase->payment_link, get_string('openlinkinnewwindow', 'local_subscriptions'), ['target' => '_blank'])
        : '-',

    get_string('digital_attempts', 'local_subscriptions') => s((string)($purchase->attempts ?? 0)),
    get_string('digital_last_attempt', 'local_subscriptions') => AdminFormatter::datetime((int)($purchase->last_attempt ?? 0)),
    get_string('digital_last_error', 'local_subscriptions') => !empty($purchase->last_error)
        ? html_writer::tag('pre', s((string)$purchase->last_error), ['class' => 'crm-json-preview'])
        : '-',

    get_string('creation_date', 'local_subscriptions') => AdminFormatter::datetime((int)($purchase->creation_date ?? 0)),
    get_string('last_update', 'local_subscriptions') => AdminFormatter::datetime((int)($purchase->last_update ?? 0)),
    get_string('digital_purchases_export_payment_date', 'local_subscriptions') => AdminFormatter::datetime((int)($purchase->payment_date ?? 0)),
    get_string('digital_purchases_export_link_expiration', 'local_subscriptions') => AdminFormatter::datetime((int)($purchase->download_token_expires ?? 0)),

    get_string('digital_created_ip', 'local_subscriptions') => s($purchase->created_ip ?? '-'),
    get_string('digital_accept_language', 'local_subscriptions') => s($purchase->accept_language ?? '-'),
    get_string('digital_http_referer', 'local_subscriptions') => !empty($purchase->http_referer)
        ? html_writer::link($purchase->http_referer, s($purchase->http_referer), ['target' => '_blank'])
        : '-',

    get_string('digital_response_json', 'local_subscriptions') => $providerdata,
];

echo AdminDetailRenderer::card(
    get_string('digital_purchase_details', 'local_subscriptions') . ' #' . $purchase->id,
    $rows
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchases_admin_page()),
    '← ' . get_string('digital_purchases', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo $OUTPUT->footer();