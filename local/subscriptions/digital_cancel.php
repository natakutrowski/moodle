<?php
require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;

\local_subscriptions\subscription_config::guard_public_access();

$pid = optional_param('pid', 0, PARAM_INT);
$uilang = optional_param('uilang', '', PARAM_ALPHANUMEXT);
$langparam = optional_param('lang', '', PARAM_ALPHANUMEXT);

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

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/digital_cancel.php', ['pid' => $pid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('digital_cancel_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_cancel_title', 'local_subscriptions'));

echo $OUTPUT->header();

echo html_writer::start_div('container my-5');
echo $OUTPUT->notification(get_string('digital_cancel_message', 'local_subscriptions'), 'warning');

if ($pid) {
    $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pid], '*', IGNORE_MISSING);
    if ($pr) {
        echo html_writer::link(
            $pr->payment_link ?: new moodle_url('/local/subscriptions/pdf_conjugaison_3e_groupe.php'),
            get_string('digital_cancel_retry', 'local_subscriptions'),
            ['class' => 'btn btn-primary mt-3']
        );
    }
}

echo html_writer::end_div();

echo $OUTPUT->footer();