<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');

use local_subscriptions\mailer;
use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

\local_subscriptions\subscription_config::guard_public_access();

$pid = required_param('pid', PARAM_INT); // payment_request_id
global $DB, $SITE;

$PAGE->set_url(UrlFactory::payment_cancel(['pid' => $pid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payment_canceled_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$paymentreq = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', IGNORE_MISSING);
if ($paymentreq && $paymentreq->status === Status::PENDING) {
    $paymentreq->status = Status::CANCELED;
    $DB->update_record('subscription_payment_request', $paymentreq);

    // Envoi d’un mail de reprise (gentil) après Cancel
    if (!empty($paymentreq->email) || !empty($paymentreq->userid)) {
        mailer::dispatch(
            mailer::T_PAYMENT_ABANDONED,[
                'pr' => $paymentreq
            ]
        );
    }
}

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('payment_canceled_msg', 'local_subscriptions'), \core\output\notification::NOTIFY_WARNING);
echo html_writer::div(
    html_writer::link(UrlFactory::subscribe(), get_string('back_to_plans', 'local_subscriptions')),
    'my-4'
);
echo $OUTPUT->footer();
