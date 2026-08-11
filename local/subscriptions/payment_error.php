<?php
require_once(__DIR__.'/../../config.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\PaymentFailureReporter;

use local_subscriptions\commerce\compatibility\CommerceLegacyUrlCompatibilityService;

\local_subscriptions\subscription_config::guard_public_access();

global $DB;
(new CommerceLegacyUrlCompatibilityService($DB))->redirect_native_result_if_present('failed');


// --- Helper format prix (respecte l’option symbole) ---
function ls_money_fmt(float $amount, string $cur): string {
    $usesymbol = (bool) get_config('local_subscriptions','display_currency_symbols');
    $amt = number_format($amount, 2, '.', '');
    $cur = strtoupper($cur ?: 'EUR');
    $sym = ($cur === 'EUR') ? '€' : (($cur === 'RUB' || $cur === 'RUR') ? '₽' : $cur);
    return $usesymbol ? ($amt.' '.$sym) : ($amt.' '.$cur);
}

$code = optional_param(
    'code',
    'session_create',
    PARAM_ALPHANUMEXT
);

$reference = optional_param(
    'reference',
    '',
    PARAM_ALPHANUMEXT
);

$pid = optional_param(
    'pid',
    0,
    PARAM_INT
);

$lang = optional_param(
    'lang',
    '',
    PARAM_ALPHANUMEXT
);

$code =
    PaymentFailureReporter::normalize_public_code(
        $code
    );
if ($lang !== '') {
    // Ne pas utiliser force_current_language() ici
    $SESSION->lang = $lang;   // langue de session "normale", modifiable via le sélecteur
    moodle_setlocale();       // réinitialise locale/strings pour cette requête
}

global $DB, $SITE, $OUTPUT, $CFG;

$pageparams = [
    'code' =>
        $code,

    'pid' =>
        $pid,
];

if ($reference !== '') {
    $pageparams['reference'] =
        $reference;
}

$PAGE->set_url(
    UrlFactory::payment_error(
        $pageparams
    )
);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payui_error_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$map = [
    'security' =>
        'payui_reason_security',

    'link' =>
        'payui_reason_link',

    'currency' =>
        'payui_reason_currency',

    'amount' =>
        'payui_reason_amount',

    'gateway' =>
        'payui_reason_gateway',

    'canceled' =>
        'payui_reason_canceled',

    'declined' =>
        'payui_reason_declined',

    'expired' =>
        'payui_reason_expired',

    'owner' =>
        'payui_reason_owner',

    'status' =>
        'payui_reason_status',

    'invalidsesskey' =>
        'payui_reason_security',

    'session_create' =>
        'payment_error_session_create',

    'payment_retry' =>
        'payment_error_retry',

    'digital_session_create' =>
        'payment_error_digital_session_create',

    'invalid_redirect' =>
        'payment_error_invalid_redirect',

    'provider_unavailable' =>
        'payment_error_provider_unavailable',

    'no_redirect' =>
        'payui_reason_link',
];

$key = $map[$code] ?? null;

$reason = $key
    ? get_string(
        $key,
        'local_subscriptions'
    )
    : get_string(
        'payui_error_generic',
        'local_subscriptions'
    );

$subtitle = get_string(
    'payui_error_subtitle',
    'local_subscriptions'
);

$generic = get_string(
    'payui_error_generic',
    'local_subscriptions'
);

$orderref = $pid
    ? get_string(
        'payui_order_ref',
        'local_subscriptions',
        $pid
    )
    : '';

$support  = get_config('local_subscriptions','support_email') ?: 'support@campusfr.fr';
$backUrl  = UrlFactory::subscribe();
$backLbl  = get_string('payui_cta_back','local_subscriptions');
$retryLbl = get_string('payui_cta_retry','local_subscriptions');
$contact  = get_string('payui_cta_contact','local_subscriptions');

$supportsubject = 'Payment error';

if ($pid) {
    $supportsubject .= ' #' . $pid;
}

if ($reference !== '') {
    $supportsubject .=
        ' [' . $reference . ']';
}

$retryUrl = null;
if ($pid) {
    $pr = $DB->get_record('subscription_payment_request', ['id'=>$pid], '*', IGNORE_MISSING);
    if ($pr && !empty($pr->planid) && !empty($pr->currency)) {
        $retryUrl = UrlFactory::checkout((int)$pr->planid, core_text::strtolower($pr->currency));
    }
}

$statusimg = new moodle_url('/local/subscriptions/pix/payment_error.png');

echo $OUTPUT->header();

echo html_writer::start_div('container my-4');
echo html_writer::start_div('card shadow-sm');
echo html_writer::div(
    html_writer::tag('h2', get_string('payui_error_title','local_subscriptions'), ['class'=>'h4 m-0']),
    'card-header bg-light'
);

echo html_writer::start_div('card-body payment-status-card');
echo html_writer::start_div('row align-items-center');

// Colonne texte
echo html_writer::start_div('col-md-7 mb-3 mb-md-0');

echo html_writer::tag('p', s($subtitle), ['class'=>'text-muted mb-2']);
echo html_writer::tag('p', s($reason),   ['class'=>'mb-2']);
echo html_writer::tag('p', s($generic),  ['class'=>'mb-3']);

if (!empty($orderref)) {
    echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');
}

if ($reference !== '') {
    echo html_writer::div(
        html_writer::span(
            get_string(
                'payment_error_reference',
                'local_subscriptions',
                s($reference)
            ),
            'small text-muted'
        ),
        'mb-3'
    );
}

// --- Prix prévu (inchangé) ---
$prLocal = null;
if (isset($pr)) {
    $prLocal = $pr;
} else if (!empty($pid)) {
    $prLocal = $DB->get_record('subscription_payment_request', ['id'=>$pid], '*', IGNORE_MISSING);
}

if ($prLocal) {
    $cur     = strtoupper($prLocal->currency ?? 'EUR');
    $list    = isset($prLocal->locked_list_price) ? (float)$prLocal->locked_list_price : null;
    $final   = (isset($prLocal->locked_final_price) && (float)$prLocal->locked_final_price > 0)
                ? (float)$prLocal->locked_final_price
                : (isset($prLocal->price) ? (float)$prLocal->price : null);
    $discPct = (int)($prLocal->locked_discount_percent ?? 0);
    $reasonDisc  = $prLocal->locked_discount_reason ?? null;

    if ($final !== null) {
        echo html_writer::div(get_string('error_price_title','local_subscriptions'), 'text-muted small mb-1');

        if ($discPct > 0 && $list !== null && $final < $list) {
            echo html_writer::start_div('fs-5');
            echo html_writer::tag('span', ls_money_fmt($list, $cur), [
                'class'=>'text-muted text-decoration-line-through me-2'
            ]);
            echo html_writer::tag('span',
                ls_money_fmt($final, $cur).' '.html_writer::tag('small','(-'.$discPct.'%)', ['class'=>'ms-1']),
                ['class'=>'fw-semibold text-success']
            );
            echo html_writer::end_div();

            if ($reasonDisc === 'trial72h') {
                echo html_writer::div(
                    get_string('reason_trial72h','local_subscriptions', $discPct),
                    'small text-muted'
                );
            }
        } else {
            echo html_writer::div(ls_money_fmt($final, $cur), 'fs-5 fw-semibold');
        }
    }
}

// Boutons
echo html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
if ($retryUrl) {
    echo html_writer::link($retryUrl, $retryLbl, ['class'=>'btn btn-primary']);
}
echo html_writer::link($backUrl, $backLbl, ['class'=>'btn btn-outline-secondary']);
echo html_writer::link(
    new moodle_url(
        'mailto:' . $support,
        [
            'subject' =>
                $supportsubject,
        ]
    ),
    $contact,
    [
        'class' =>
            'btn btn-link',
    ]
);
echo html_writer::end_div(); // ctas

echo html_writer::end_div(); // col texte

// Colonne image
echo html_writer::start_div('col-md-5 text-center');
echo html_writer::start_div('payment-status-illustration-wrapper');
echo html_writer::empty_tag('img', [
    'src'   => $statusimg->out(false),
    'alt'   => get_string('paymenterror_mascot_alt', 'local_subscriptions'),
    'class' => 'img-fluid payment-status-illustration'
]);
echo html_writer::end_div(); // wrapper
echo html_writer::end_div(); // col image

echo html_writer::end_div(); // row
echo html_writer::end_div(); // card
echo html_writer::end_div(); // container


echo $OUTPUT->footer();
