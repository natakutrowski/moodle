<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');

use local_subscriptions\mailer;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

\local_subscriptions\subscription_config::guard_public_access();

$pid = required_param('pid', PARAM_INT); // payment_request_id
global $DB, $SITE, $OUTPUT;

$PAGE->set_url(UrlFactory::payment_cancel(['pid' => $pid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payui_error_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$pr = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', IGNORE_MISSING);
if ($pr && $pr->status === Status::PENDING) {
    $pr->status = Status::CANCELED;
    $pr->last_update = time();
    $DB->update_record('subscription_payment_request', $pr);

    if (!empty($pr->email) || !empty($pr->userid)) {
        mailer::dispatch(mailer::T_PAYMENT_ABANDONED, ['pr' => $pr]);
    }
}

$support  = get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr';
$reason   = get_string('payui_reason_canceled',   'local_subscriptions');
$subtitle = get_string('payui_error_subtitle',    'local_subscriptions');
$generic  = get_string('payui_error_generic',     'local_subscriptions');
$orderref = get_string('payui_order_ref',         'local_subscriptions', $pid);

$backUrl  = UrlFactory::subscribe();
$backLbl  = get_string('payui_cta_back',          'local_subscriptions');
$retryLbl = get_string('payui_cta_retry',         'local_subscriptions');
$contact  = get_string('payui_cta_contact',       'local_subscriptions');

$retryUrl = null;
if ($pr && !empty($pr->planid) && !empty($pr->currency)) {
    $retryUrl = UrlFactory::checkout((int)$pr->planid, core_text::strtolower($pr->currency));
}

echo $OUTPUT->header();

echo html_writer::start_div('container my-4');
echo html_writer::start_div('card shadow-sm');
echo html_writer::div(html_writer::tag('h2', $reason, ['class'=>'h4 m-0']), 'card-header bg-light');

echo html_writer::start_div('card-body');
echo html_writer::tag('p', s($subtitle), ['class'=>'text-muted mb-2']);
echo html_writer::tag('p', s($generic),  ['class'=>'mb-3']);
echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');

if ($pr && isset($pr->price, $pr->currency)) {
    echo html_writer::div(
        html_writer::span(get_string('payui_label_price','local_subscriptions').': ', 'text-muted').
        html_writer::span(format_float((float)$pr->price,2).' '.s(strtoupper($pr->currency)), 'fw-semibold'),
        'mb-2'
    );
}
if ($pr && !empty($pr->planid)) {
    $planname = $DB->get_field('subscription_plan', 'name', ['id'=>(int)$pr->planid], IGNORE_MISSING) ?: '';
    if ($planname !== '') {
        echo html_writer::div(
            html_writer::span(get_string('payui_label_plan','local_subscriptions').': ', 'text-muted').
            html_writer::span(format_string($planname), 'fw-semibold'),
            'mb-2'
        );
    }
}

echo html_writer::start_div('d-flex gap-2 mt-2');
if ($retryUrl) {
    echo html_writer::link($retryUrl, $retryLbl, ['class'=>'btn btn-primary']);
}
echo html_writer::link($backUrl, $backLbl, ['class'=>'btn btn-outline-secondary']);
echo html_writer::link(
    new moodle_url('mailto:'.$support, ['subject'=>'Payment canceled #'.$pid]),
    $contact,
    ['class'=>'btn btn-link']
);
echo html_writer::end_div(); // ctas

echo html_writer::end_div(); // body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // container

echo $OUTPUT->footer();
