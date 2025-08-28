<?php
require_once(__DIR__.'/../../config.php');

$code = optional_param('code', '', PARAM_ALPHANUMEXT);
$msg  = optional_param('msg', '', PARAM_RAW_TRIMMED);

$PAGE->set_url(new moodle_url('/local/subscriptions/payment_error.php', ['code'=>$code]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payment_error_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('payment_error_intro', 'local_subscriptions'), \core\output\notification::NOTIFY_ERROR);

if ($msg) {
    echo html_writer::div(html_writer::tag('pre', s($msg), ['class'=>'small text-muted']), 'mt-2');
}

echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/subscribe.php'), get_string('back_to_plans', 'local_subscriptions'), ['class'=>'btn btn-secondary']),
    'mt-3'
);
echo $OUTPUT->footer();
