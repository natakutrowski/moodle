<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerEmailChangeService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);
$userid = required_param('id', PARAM_INT);
$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
$url = new moodle_url(subscription_config::admin_user_change_email_page(), ['id' => $userid]);
$returnurl = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]);
$title = get_string('commerce_customer_email_change_title', 'local_subscriptions');

CrmPageConfigurator::configure($PAGE, $context, $url, $title, [
    'local-subscriptions-user-profile-page',
    'local-subscriptions-user-change-email-page',
]);

class local_subscriptions_change_email_form extends moodleform {
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'newemail', get_string('newemail'));
        $mform->setType('newemail', PARAM_EMAIL);
        $mform->addRule('newemail', null, 'required', null, 'client');
        $mform->addElement('advcheckbox', 'confirmchange', get_string('commerce_customer_email_change_confirm', 'local_subscriptions'));
        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'previewbutton', get_string('commerce_customer_email_change_preview', 'local_subscriptions'));
        $buttons[] = $mform->createElement('submit', 'executebutton', get_string('commerce_customer_email_change_execute', 'local_subscriptions'), ['class' => 'btn btn-danger']);
        $buttons[] = $mform->createElement('cancel');
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
    }
}

$service = new CommerceCustomerEmailChangeService($DB);
$form = new local_subscriptions_change_email_form($url);
$form->set_data(['id' => $userid, 'newemail' => (string)$user->email]);
$error = null;
$preview = null;

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    require_sesskey();
    try {
        $preview = $service->preview($userid, (string)$data->newemail);
        if (!empty($data->executebutton)) {
            if (empty($data->confirmchange)) {
                throw new moodle_exception('commerce_customer_email_change_confirmation_required', 'local_subscriptions');
            }
            $service->change($userid, (string)$data->newemail, (int)$USER->id);
            redirect($returnurl, get_string('commerce_customer_email_change_success', 'local_subscriptions'));
        }
        $form->set_data($data);
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
        $form->set_data($data);
    }
} else {
    try {
        $preview = $service->preview($userid, (string)$user->email);
    } catch (\Throwable $ignored) {
        $preview = null;
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::USERS, $context);
echo CrmBackLinkRenderer::render($returnurl, get_string('back'));
echo CrmPageHeader::render($title, get_string('commerce_customer_email_change_description', 'local_subscriptions'), HelpContext::USER_PROFILE);

echo html_writer::div(
    get_string('commerce_customer_email_change_current', 'local_subscriptions', (object)['email' => (string)$user->email]),
    'alert alert-light border'
);
if ($error !== null) {
    echo html_writer::div(s($error), 'alert alert-danger');
}

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_customer_email_change_scope_title', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::tag('ul',
    html_writer::tag('li', get_string('commerce_customer_email_change_scope_current', 'local_subscriptions')) .
    html_writer::tag('li', get_string('commerce_customer_email_change_scope_history', 'local_subscriptions')),
    ['class' => 'mb-0']
);
echo html_writer::end_div();

$form->display();
if ($preview !== null && $preview['oldemail'] !== $preview['newemail']) {
    echo html_writer::start_div('card card-body mt-4 border-primary');
    echo html_writer::tag('h3', get_string('commerce_customer_email_change_preview_title', 'local_subscriptions'), ['class' => 'h6']);
    echo html_writer::div(s($preview['oldemail']) . ' → ' . s($preview['newemail']), 'fw-semibold mb-3');
    echo html_writer::div(get_string('commerce_customer_email_change_preview_current', 'local_subscriptions', (object)['count' => (int)$preview['currenttotal']]), 'mb-2');
    echo html_writer::div(get_string('commerce_customer_email_change_preview_history', 'local_subscriptions', (object)['count' => (int)$preview['historicaltotal']]), 'text-muted small');
    echo html_writer::end_div();
}
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
