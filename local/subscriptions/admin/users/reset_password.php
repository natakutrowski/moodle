<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserEmailService;
use local_subscriptions\admin\AdminLog;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$id = required_param('id', PARAM_INT);

$user = $DB->get_record('user', [
    'id' => $id,
    'deleted' => 0,
], '*', MUST_EXIST);

$url = new moodle_url('/local/subscriptions/admin/users/reset_password.php', ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('crm_reset_password', 'local_subscriptions') . ' - ' . fullname($user));
$PAGE->set_heading(get_string('crm_reset_password', 'local_subscriptions'));

class local_subscriptions_crm_reset_password_form extends moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'));
        $mform->setType('newpassword', PARAM_RAW_TRIMMED);
        $mform->addRule('newpassword', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'notifyuser', get_string('crm_notify_user_by_email', 'local_subscriptions'));
        $mform->setDefault('notifyuser', 1);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $password = (string)($data['newpassword'] ?? '');

        if (core_text::strlen($password) < 8) {
            $errors['newpassword'] = get_string('crm_password_too_short', 'local_subscriptions');
        }

        return $errors;
    }
}

$form = new local_subscriptions_crm_reset_password_form($url);
$form->set_data(['id' => $id]);

if ($form->is_cancelled()) {
    redirect(new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]));
}

if ($data = $form->get_data()) {
    require_sesskey();

    $updated = clone $user;
    $updated->password = hash_internal_user_password((string)$data->newpassword);
    $updated->timemodified = time();

    user_update_user($updated, false, false);

    AdminLog::log(
    'user.password.updated',
        (int)$user->id,
        'user',
        (int)$user->id,
        ['notifyuser' => !empty($data->notifyuser)]
    );

    if (!empty($data->notifyuser)) {
        UserEmailService::send_password_reset_notice(
            (int)$user->id,
            (string)$data->newpassword
        );
    }

    redirect(
        new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]),
        get_string('crm_password_updated_successfully', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]),
    '← ' . get_string('back'),
    ['class' => 'btn btn-outline-secondary mb-3']
);

echo html_writer::div(
    html_writer::tag('h4', fullname($user), ['class' => 'mb-1']) .
    html_writer::div(s($user->email), 'text-muted'),
    'card card-body mb-4'
);

echo $OUTPUT->notification(get_string('crm_reset_password_warning', 'local_subscriptions'), 'warning');

$form->display();

echo $OUTPUT->footer();