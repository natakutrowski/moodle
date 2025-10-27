<?php
require_once(__DIR__.'/../../config.php');

use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$code = optional_param('code', '', PARAM_ALPHANUMEXT);
$msg  = optional_param('msg',  '', PARAM_RAW_TRIMMED);
$pid  = optional_param('pid',  0,    PARAM_INT);

global $DB, $SITE, $OUTPUT, $CFG;

$PAGE->set_url(UrlFactory::payment_error(['code'=>$code, 'pid'=>$pid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payui_error_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$map = [
    'security'       => 'payui_reason_security',
    'link'           => 'payui_reason_link',
    'currency'       => 'payui_reason_currency',
    'amount'         => 'payui_reason_amount',
    'gateway'        => 'payui_reason_gateway',
    'canceled'       => 'payui_reason_canceled',
    'declined'       => 'payui_reason_declined',
    'expired'        => 'payui_reason_expired',
    'owner'          => 'payui_reason_owner',
    'status'         => 'payui_reason_status',
    'invalidsesskey' => 'payui_reason_security',
    'session_create' => 'payui_reason_gateway',
    'no_redirect'    => 'payui_reason_link',
];

$key      = $map[$code] ?? null;
$reason   = $key ? get_string($key,'local_subscriptions') : get_string('payui_error_generic','local_subscriptions');
$subtitle = get_string('payui_error_subtitle','local_subscriptions');
$generic  = get_string('payui_error_generic','local_subscriptions');
$orderref = $pid ? get_string('payui_order_ref','local_subscriptions',$pid) : '';

$support  = get_config('local_subscriptions','support_email') ?: 'support@campusfr.fr';
$backUrl  = UrlFactory::subscribe();
$backLbl  = get_string('payui_cta_back','local_subscriptions');
$retryLbl = get_string('payui_cta_retry','local_subscriptions');
$contact  = get_string('payui_cta_contact','local_subscriptions');

$retryUrl = null;
if ($pid) {
    $pr = $DB->get_record('subscription_payment_request', ['id'=>$pid], '*', IGNORE_MISSING);
    if ($pr && !empty($pr->planid) && !empty($pr->currency)) {
        $retryUrl = UrlFactory::checkout((int)$pr->planid, core_text::strtolower($pr->currency));
    }
}

echo $OUTPUT->header();

echo html_writer::start_div('container my-4');
echo html_writer::start_div('card shadow-sm');
echo html_writer::div(
    html_writer::tag('h2', get_string('payui_error_title','local_subscriptions'), ['class'=>'h4 m-0']),
    'card-header bg-light'
);

echo html_writer::start_div('card-body');
echo html_writer::tag('p', s($subtitle), ['class'=>'text-muted mb-2']);
echo html_writer::tag('p', s($reason),   ['class'=>'mb-2']);
echo html_writer::tag('p', s($generic),  ['class'=>'mb-3']);

if (!empty($orderref)) {
    echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');
}

if ($msg && (is_siteadmin() || !empty($CFG->debugdeveloper))) {
    echo html_writer::div(html_writer::tag('pre', s($msg), ['class'=>'small text-muted']), 'mt-2');
}

echo html_writer::start_div('d-flex gap-2 mt-2');
if ($retryUrl) {
    echo html_writer::link($retryUrl, $retryLbl, ['class'=>'btn btn-primary']);
}
echo html_writer::link($backUrl, $backLbl, ['class'=>'btn btn-outline-secondary']);
echo html_writer::link(
    new moodle_url('mailto:'.$support, ['subject'=>'Payment error '.($pid ? '#'.$pid : '')]),
    $contact,
    ['class'=>'btn btn-link']
);
echo html_writer::end_div();

echo html_writer::end_div(); // body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // container

echo $OUTPUT->footer();
