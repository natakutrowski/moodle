<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserEmailService;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminEvents;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmReturnUrlResolver;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $DB, $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

$id = required_param('id', PARAM_INT);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$user = $DB->get_record('user', [
    'id' => $id,
    'deleted' => 0,
], '*', MUST_EXIST);

$urlparams = [
    'id' => $id,
];

if ($returnurl !== '') {
    $urlparams['returnurl'] =
        $returnurl;
}

$url = new moodle_url(
    subscription_config::
        admin_user_reset_password_page(),
    $urlparams
);

$pagetitle =
    get_string(
        'crm_reset_password',
        'local_subscriptions'
    ) .
    ' - ' .
    fullname($user);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    [
        'local-subscriptions-user-profile-page',
        'local-subscriptions-user-reset-password-page',
    ]
);

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

$fallbackurl = new moodle_url(
    subscription_config::
        admin_user_view_page(),
    [
        'id' => $id,
    ]
);

$redirecturl =
    CrmReturnUrlResolver::resolve(
        $returnurl,
        $fallbackurl
    );

if ($form->is_cancelled()) {
    redirect($redirecturl);
}

if ($data = $form->get_data()) {
    require_sesskey();

    $updated = clone $user;
    $updated->password = hash_internal_user_password((string)$data->newpassword);
    $updated->timemodified = time();

    user_update_user($updated, false, false);

    AdminLog::log(
        AdminEvents::USER_PASSWORD_UPDATED,
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
        $redirecturl,
        get_string('crm_password_updated_successfully', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::USERS,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'admin_users',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_users_page()
                ),
        ],
        [
            'label' =>
                fullname($user),

            'url' =>
                $fallbackurl,
        ],
        [
            'label' =>
                get_string(
                    'crm_reset_password',
                    'local_subscriptions'
                ),

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    $redirecturl,
    get_string(
        'back',
        'core'
    )
);

echo CrmPageHeader::render(
    get_string(
        'crm_reset_password',
        'local_subscriptions'
    ),
    fullname($user) .
        ' · ' .
        $user->email,
    HelpContext::USER_PROFILE
);

echo $OUTPUT->notification(get_string('crm_reset_password_warning', 'local_subscriptions'), 'warning');

$form->display();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();