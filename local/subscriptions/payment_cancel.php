<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');

use local_subscriptions\mailer;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

\local_subscriptions\subscription_config::guard_public_access();

// --- Helper format prix (respecte l’option symbole) ---
function ls_money_fmt(float $amount, string $cur): string {
    $usesymbol = (bool) get_config('local_subscriptions','display_currency_symbols');
    $amt = number_format($amount, 2, '.', '');
    $cur = strtoupper($cur ?: 'EUR');
    $sym = ($cur === 'EUR') ? '€' : (($cur === 'RUB' || $cur === 'RUR') ? '₽' : $cur);
    return $usesymbol ? ($amt.' '.$sym) : ($amt.' '.$cur);
}


$pid = required_param('pid', PARAM_INT); // payment_request_id
$lang = optional_param('lang','', PARAM_ALPHANUMEXT);
if ($lang !== '') {
    // Ne pas utiliser force_current_language() ici
    $SESSION->lang = $lang;   // langue de session "normale", modifiable via le sélecteur
    moodle_setlocale();       // réinitialise locale/strings pour cette requête
}


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

$statusimg = new moodle_url('/local/subscriptions/pix/payment_cancel.png');

$retryUrl = null;
if ($pr && !empty($pr->planid) && !empty($pr->currency)) {
    $retryUrl = UrlFactory::checkout((int)$pr->planid, core_text::strtolower($pr->currency));
}

echo $OUTPUT->header();

echo html_writer::start_div('container my-4');
echo html_writer::start_div('card shadow-sm payment-status-card');
echo html_writer::div(html_writer::tag('h2', $reason, ['class'=>'h4 m-0']), 'card-header bg-light');

echo html_writer::start_div('card-body');
echo html_writer::start_div('row align-items-center');

// Colonne texte
echo html_writer::start_div('col-md-7 mb-3 mb-md-0');

echo html_writer::tag('p', s($subtitle), ['class'=>'text-muted mb-2']);
echo html_writer::tag('p', s($generic),  ['class'=>'mb-3']);
echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');

// --- Prix prévu (inchangé) ---
if ($pr) {
    $cur     = strtoupper($pr->currency ?? 'EUR');
    $list    = isset($pr->locked_list_price) ? (float)$pr->locked_list_price : null;
    $final   = (isset($pr->locked_final_price) && (float)$pr->locked_final_price > 0)
                ? (float)$pr->locked_final_price
                : (isset($pr->price) ? (float)$pr->price : null);
    $discPct = (int)($pr->locked_discount_percent ?? 0);

    if ($final !== null) {
        echo html_writer::div(
            get_string('payui_label_price','local_subscriptions').':',
            'text-muted small mb-1'
        );

        if ($discPct > 0 && $list !== null && $final < $list) {
            echo html_writer::start_div('fs-5');
            echo html_writer::tag('span', ls_money_fmt($list, $cur), [
                'class' => 'text-muted text-decoration-line-through me-2'
            ]);
            echo html_writer::tag('span',
                ls_money_fmt($final, $cur) . ' ' .
                html_writer::tag('small', '(-'.$discPct.'%)', ['class'=>'ms-1']),
                ['class' => 'fw-semibold text-success']
            );
            echo html_writer::end_div();
        } else {
            echo html_writer::div(ls_money_fmt($final, $cur), 'fs-5 fw-semibold');
        }
    }
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

echo html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
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

echo html_writer::end_div(); // col texte

// Colonne image
echo html_writer::start_div('col-md-5 text-center');
echo html_writer::start_div('payment-status-illustration-wrapper');
echo html_writer::empty_tag('img', [
    'src'   => $statusimg->out(false),
    'alt'   => get_string('paymentcancel_mascot_alt', 'local_subscriptions'),
    'class' => 'img-fluid payment-status-illustration'
]);
echo html_writer::end_div(); // wrapper
echo html_writer::end_div(); // col image

echo html_writer::end_div(); // row
echo html_writer::end_div(); // body
echo html_writer::end_div(); // card
echo html_writer::end_div(); // container


echo $OUTPUT->footer();
