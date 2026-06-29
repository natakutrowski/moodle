<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserEmailService;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);

$id = required_param('id', PARAM_INT);

$user = $DB->get_record('user', [
    'id' => $id,
    'deleted' => 0,
], '*', MUST_EXIST);

$url = new moodle_url('/local/subscriptions/admin/users/email.php', ['id' => $id]);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('crm_send_email', 'local_subscriptions') . ' - ' . fullname($user));
$PAGE->set_heading(get_string('crm_send_email', 'local_subscriptions'));

class local_subscriptions_crm_email_form extends moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'subject', get_string('subject', 'local_subscriptions'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('editor', 'message', get_string('message', 'local_subscriptions'), [
            'rows' => 12,
        ], [
            'trusttext' => false,
            'subdirs' => false,
            'maxfiles' => 0,
            'context' => context_system::instance(),
        ]);
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', null, 'required', null, 'client');

        $mform->addElement('header', 'ctaheader', get_string('crm_email_button_optional', 'local_subscriptions'));

        $mform->addElement('text', 'buttonlabel', get_string('crm_email_button_label', 'local_subscriptions'));
        $mform->setType('buttonlabel', PARAM_TEXT);

        $mform->addElement('text', 'buttonurl', get_string('crm_email_button_url', 'local_subscriptions'));
        $mform->setType('buttonurl', PARAM_URL);

        $this->add_action_buttons(true, get_string('send', 'local_subscriptions'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $buttonlabel = trim((string)($data['buttonlabel'] ?? ''));
        $buttonurl = trim((string)($data['buttonurl'] ?? ''));

        if ($buttonlabel !== '' && $buttonurl === '') {
            $errors['buttonurl'] = get_string('crm_email_button_url_required', 'local_subscriptions');
        }

        if ($buttonurl !== '' && $buttonlabel === '') {
            $errors['buttonlabel'] = get_string('crm_email_button_label_required', 'local_subscriptions');
        }

        return $errors;
    }
}

$form = new local_subscriptions_crm_email_form($url);

$form->set_data([
    'id' => $id,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]));
}

if ($data = $form->get_data()) {
    UserEmailService::send_custom_email(
        (int)$data->id,
        (string)$data->subject,
        (string)$data->message['text'],
        $data->buttonlabel ?? null,
        $data->buttonurl ?? null
    );

    redirect(
        new moodle_url(subscription_config::admin_user_view_page(), ['id' => $id]),
        get_string('crm_email_sent_successfully', 'local_subscriptions'),
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

$form->display();

echo $OUTPUT->footer();