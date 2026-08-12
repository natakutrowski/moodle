<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once(
    __DIR__
    . '/forms/commerce/customer/CommerceLegacyDigitalAccountActivationForm.php'
);

use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalAccountActivationService;
use local_subscriptions\form\commerce\customer\CommerceLegacyDigitalAccountActivationForm;
use local_subscriptions\url\UrlFactory;

\local_subscriptions\subscription_config::guard_public_access();

$uid = required_param('uid', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(
    new moodle_url(
        '/local/subscriptions/legacy_account_activate.php',
        [
            'uid' => $uid,
            'key' => $key,
        ]
    )
);
$PAGE->set_pagelayout('login');
$PAGE->add_body_class('commerce-guest-activation-page');
$PAGE->add_body_class('commerce-chromeless-page');
$PAGE->set_title(
    get_string(
        'commerce_legacy_account_activation_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'commerce_legacy_account_activation_title',
        'local_subscriptions'
    )
);
$PAGE->requires->css(
    new moodle_url(
        '/local/subscriptions/styles/guest_account_activation.css'
    )
);

$passwordpolicy = [
    'minlength' => max(0, (int)$CFG->minpasswordlength),
    'minlower' => max(0, (int)$CFG->minpasswordlower),
    'minupper' => max(0, (int)$CFG->minpasswordupper),
    'mindigits' => max(0, (int)$CFG->minpassworddigits),
    'minspecial' => max(
        0,
        (int)$CFG->minpasswordnonalphanum
    ),
];

$PAGE->requires->js_call_amd(
    'local_subscriptions/guest_account_activation',
    'init',
    [
        get_string(
            'commerce_guest_activation_show_password',
            'local_subscriptions'
        ),
        get_string(
            'commerce_guest_activation_hide_password',
            'local_subscriptions'
        ),
        $passwordpolicy,
    ]
);

$service =
    new CommerceLegacyDigitalAccountActivationService($DB);

try {
    $user = $service->validate($key, $uid);
} catch (\Throwable $exception) {
    throw new moodle_exception(
        'commerce_legacy_account_activation_invalid',
        'local_subscriptions'
    );
}

$form =
    new CommerceLegacyDigitalAccountActivationForm(
        null,
        [
            'uid' => $uid,
            'key' => $key,
        ]
    );

if ($data = $form->get_data()) {
    try {
        $service->complete(
            (string)$data->key,
            (int)$data->uid,
            (string)$data->password,
            true
        );

        redirect(
            UrlFactory::my_campus()
        );
    } catch (\Throwable $exception) {
        throw new moodle_exception(
            'commerce_legacy_account_activation_failed',
            'local_subscriptions',
            '',
            null,
            $exception->getMessage()
        );
    }
}

echo $OUTPUT->header();

echo html_writer::start_div(
    'commerce-guest-activation',
    [
        'id' => 'activation',
        'tabindex' => '-1',
    ]
);
echo html_writer::start_div(
    'commerce-guest-activation__card'
);

echo html_writer::start_div(
    'commerce-guest-activation__header'
);
echo html_writer::div(
    '🔐',
    'commerce-guest-activation__icon',
    ['aria-hidden' => 'true']
);
echo html_writer::tag(
    'h1',
    get_string(
        'commerce_legacy_account_activation_title',
        'local_subscriptions'
    ),
    ['class' => 'commerce-guest-activation__title']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_legacy_account_activation_intro',
        'local_subscriptions',
        (object)[
            'firstname' => format_string(
                (string)$user->firstname
            ),
            'email' => s((string)$user->email),
        ]
    ),
    ['class' => 'commerce-guest-activation__intro']
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-guest-activation__email'
);
echo html_writer::div(
    '✉',
    'commerce-guest-activation__email-icon',
    ['aria-hidden' => 'true']
);
echo html_writer::start_div(
    'commerce-guest-activation__email-content'
);
echo html_writer::div(
    get_string(
        'commerce_guest_activation_email_label',
        'local_subscriptions'
    ),
    'commerce-guest-activation__email-label'
);
echo html_writer::div(
    s((string)$user->email),
    'commerce-guest-activation__email-address'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-guest-activation__form'
);
$form->display();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
