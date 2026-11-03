<?php

/** CRM-only, confirmed, student-scoped impersonation action. */
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\CrmStudentImpersonation;

/** @var moodle_database $DB */
global $DB, $PAGE, $OUTPUT;

$systemcontext = AdminSecurity::require(Capabilities::VIEW_USERS);
require_capability(Capabilities::LOGIN_AS_STUDENT, $systemcontext);

$userid = required_param('id', PARAM_INT);
if ($userid <= 1) {
    throw new moodle_exception('invalidparameter');
}
$target = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
// A site admin testing a Support account can inspect eligibility only.
// Moodle core refuses a second nested login-as; this route never bypasses it.
$previewonly = CrmStudentImpersonation::can_preview($target);
if (!$previewonly && !CrmStudentImpersonation::can_start($target)) {
    throw new moodle_exception('nopermissions', 'error');
}

$url = new moodle_url('/local/subscriptions/admin/users/login_as_student.php', ['id' => $userid]);
$profileurl = new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => $userid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    if (required_param('action', PARAM_ALPHA) !== 'loginas') {
        throw new moodle_exception('invalidparameter');
    }
    // Never attempt a nested login-as: Moodle's session manager would do nothing.
    if (\core\session\manager::is_loggedinas()
            || !CrmStudentImpersonation::can_start($target)) {
        throw new moodle_exception('nopermissions', 'error');
    }
    // Core session manager generates Moodle's native impersonation audit event.
    // No global moodle/user:loginas permission is assigned to CRM Support.
    \core\session\manager::loginas($userid, $systemcontext);
    \core\notification::info(get_string('sessionforceclean', 'core'));
    redirect(\local_subscriptions\url\UrlFactory::my_campus(),
        get_string('crm_login_as_student_started', 'local_subscriptions', fullname($target)));
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    throw new moodle_exception('invalidparameter');
}

$title = get_string('crm_login_as_student_title', 'local_subscriptions');
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo html_writer::start_div('container-fluid', ['style' => 'max-width:760px']);
echo html_writer::tag('h2', s(fullname($target)));
echo html_writer::div(
    s(get_string($previewonly ? 'crm_login_as_admin_preview' : 'crm_login_as_student_warning',
        'local_subscriptions')),
    $previewonly ? 'alert alert-info my-4' : 'alert alert-warning my-4'
);
if (!$previewonly) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'id', 'value' => $userid,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden', 'name' => 'action', 'value' => 'loginas',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'class' => 'btn btn-danger mr-3',
        'value' => get_string('crm_login_as_student_confirm', 'local_subscriptions'),
    ]);
    echo html_writer::end_tag('form');
}
echo html_writer::div(html_writer::link($profileurl,
    get_string('crm_login_as_student_cancel', 'local_subscriptions')),
    'mt-3');
echo html_writer::end_div();
echo $OUTPUT->footer();
