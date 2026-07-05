<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\mail\MailRenderer;
use local_subscriptions\mailer;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$logid = required_param('logid', PARAM_INT);

$log = $DB->get_record('local_subscriptions_admin_log', [
    'id' => $logid,
], '*', MUST_EXIST);

$details = json_decode((string)$log->details, true);
if (!is_array($details)) {
    $details = [];
}

$userid = (int)$log->targetuserid;

$user = $DB->get_record('user', [
    'id' => $userid,
    'deleted' => 0,
], '*', MUST_EXIST);

$subject = (string)($details['subject'] ?? '');
$body = (string)($details['body'] ?? '');
$buttonlabel = trim((string)($details['buttonlabel'] ?? ''));
$buttonurl = trim((string)($details['buttonurl'] ?? ''));

$buttonhtml = '';
if ($buttonlabel !== '' && $buttonurl !== '') {
    $buttonhtml = mailer::email_button($buttonurl, s($buttonlabel));
}

$url = new moodle_url(subscription_config::admin_user_email_preview_page(), ['logid' => $logid]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('crm_email_preview', 'local_subscriptions'));
$PAGE->set_heading(get_string('crm_email_preview', 'local_subscriptions'));

[$html, $text] = MailRenderer::layout(
    $subject,
    format_text($body, FORMAT_HTML) . $buttonhtml,
    '',
    ''
);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
    '← ' . get_string('back'),
    ['class' => 'btn btn-outline-secondary mb-3']
);

echo html_writer::div(
    html_writer::tag('h4', s($subject), ['class' => 'mb-1']) .
    html_writer::div(s($user->email), 'text-muted'),
    'card card-body mb-4'
);

echo html_writer::tag('iframe', '', [
    'srcdoc' => $html,
    'style' => 'width:100%;min-height:800px;border:1px solid #dee2e6;border-radius:8px;background:white;',
]);

echo $OUTPUT->footer();