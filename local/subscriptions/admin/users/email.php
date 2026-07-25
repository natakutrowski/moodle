<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserEmailService;
use local_subscriptions\crm\user\UserProfileRepository;
use local_subscriptions\crm\user\email\UserEmailPresetBuilder;
use local_subscriptions\digital\repositories\DigitalPurchaseAdminActionRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmReturnUrlResolver;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

$id = required_param('id', PARAM_INT);

$preset = UserEmailPresetBuilder::normalize(
    optional_param('preset', '', PARAM_ALPHANUMEXT)
);

$purchaseid = optional_param('purchaseid', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$userrepository = new UserProfileRepository();
$user = $userrepository->get_user($id);

$urlparams = [
    'id' => $id,
];

if ($preset !== '') {
    $urlparams['preset'] = $preset;
}

if ($purchaseid > 0) {
    $urlparams['purchaseid'] = $purchaseid;
}

if ($returnurl !== '') {
    $urlparams['returnurl'] = $returnurl;
}

$url = new moodle_url(
    subscription_config::admin_user_email_page(),
    $urlparams
);

$pagetitle =
    get_string(
        'crm_send_email',
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
        'local-subscriptions-user-email-page',
    ]
);

class local_subscriptions_crm_email_form extends moodleform {

    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement(
            'text',
            'subject',
            get_string('subject', 'local_subscriptions')
        );
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule(
            'subject',
            null,
            'required',
            null,
            'client'
        );

        $mform->addElement(
            'editor',
            'message',
            get_string('message', 'local_subscriptions'),
            [
                'rows' => 12,
            ],
            [
                'trusttext' => false,
                'subdirs' => false,
                'maxfiles' => 0,
                'context' => context_system::instance(),
            ]
        );
        $mform->setType('message', PARAM_RAW);
        $mform->addRule(
            'message',
            null,
            'required',
            null,
            'client'
        );

        $mform->addElement(
            'header',
            'ctaheader',
            get_string(
                'crm_email_button_optional',
                'local_subscriptions'
            )
        );

        $mform->addElement(
            'text',
            'buttonlabel',
            get_string(
                'crm_email_button_label',
                'local_subscriptions'
            )
        );
        $mform->setType('buttonlabel', PARAM_TEXT);

        $mform->addElement(
            'text',
            'buttonurl',
            get_string(
                'crm_email_button_url',
                'local_subscriptions'
            )
        );
        $mform->setType('buttonurl', PARAM_URL);

        $this->add_action_buttons(
            true,
            get_string('send', 'local_subscriptions')
        );
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $buttonlabel = trim(
            (string)($data['buttonlabel'] ?? '')
        );

        $buttonurl = trim(
            (string)($data['buttonurl'] ?? '')
        );

        if ($buttonlabel !== '' && $buttonurl === '') {
            $errors['buttonurl'] = get_string(
                'crm_email_button_url_required',
                'local_subscriptions'
            );
        }

        if ($buttonurl !== '' && $buttonlabel === '') {
            $errors['buttonlabel'] = get_string(
                'crm_email_button_label_required',
                'local_subscriptions'
            );
        }

        return $errors;
    }
}

$form = new local_subscriptions_crm_email_form($url);

$formdata = [
    'id' => $id,
];

if (
    $preset === UserEmailPresetBuilder::DIGITAL_PAYMENT_HELP &&
    $purchaseid > 0
) {
    $purchaserepository =
        new DigitalPurchaseAdminActionRepository();

    $purchase = $purchaserepository->get_by_id($purchaseid);

    $purchaseuserid = !empty($purchase->userid)
        ? (int)$purchase->userid
        : 0;

    $purchaseemail = core_text::strtolower(
        trim((string)($purchase->email ?? ''))
    );

    $useremail = core_text::strtolower(
        trim((string)$user->email)
    );

    /*
     * Protection contre la manipulation des paramètres :
     * l’achat doit appartenir à cet utilisateur, par ID ou email.
     */
    if (
        $purchaseuserid !== $id &&
        ($purchaseemail === '' || $purchaseemail !== $useremail)
    ) {
        throw new moodle_exception(
            'digital_payment_help_purchase_user_mismatch',
            'local_subscriptions'
        );
    }

    $emailpreset = (new UserEmailPresetBuilder())->build(
        $preset,
        $user,
        $purchase
    );

    if ($emailpreset !== null) {
        $formdata = array_merge(
            $formdata,
            $emailpreset->to_form_data()
        );
    }
}

$form->set_data($formdata);

$fallbackurl = new moodle_url(
    subscription_config::admin_user_view_page(),
    ['id' => $id]
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
    $userid = (int)$data->id;
    $subject = trim((string)$data->subject);
    $body = (string)$data->message['text'];

    $buttonlabel = trim(
        (string)($data->buttonlabel ?? '')
    );

    $buttonurl = trim(
        (string)($data->buttonurl ?? '')
    );

    UserEmailService::send_custom_email(
        $userid,
        $subject,
        $body,
        $buttonlabel !== '' ? $buttonlabel : null,
        $buttonurl !== '' ? $buttonurl : null
    );

    redirect(
        $redirecturl,
        get_string(
            'crm_email_sent_successfully',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::USERS,
    $context
);

$userurl = new moodle_url(
    subscription_config::
        admin_user_view_page(),
    [
        'id' => $user->id,
    ]
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_users',
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
                $userurl,
        ],
        [
            'label' =>
                get_string(
                    'crm_send_email',
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
        'crm_send_email',
        'local_subscriptions'
    ),
    fullname($user) .
        ' · ' .
        $user->email,
    HelpContext::EMAIL
);

if ($preset === UserEmailPresetBuilder::DIGITAL_PAYMENT_HELP) {
    echo html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'digital_payment_help_email_context_title',
                'local_subscriptions'
            )
        ) .
        html_writer::div(
            get_string(
                'digital_payment_help_email_context_description',
                'local_subscriptions'
            ),
            'small text-muted mt-1'
        ),
        'alert alert-info mb-4'
    );
}

$form->display();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();