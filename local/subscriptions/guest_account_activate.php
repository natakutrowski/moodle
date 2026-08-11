<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;
require_once(__DIR__ . '/forms/commerce/checkout/CommerceGuestAccountActivationForm.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\form\commerce\checkout\CommerceGuestAccountActivationForm;

\local_subscriptions\subscription_config::guard_public_access();

$uid = required_param('uid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);
$key = required_param('key', PARAM_ALPHANUM);
$reference = optional_param('reference', '', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/guest_account_activate.php', [
    'uid' => $uid,
    'sessionid' => $sessionid,
    'key' => $key,
    'reference' => $reference,
]));
$PAGE->set_pagelayout('login');
$PAGE->add_body_class('commerce-guest-activation-page');
$PAGE->add_body_class('commerce-chromeless-page');
$PAGE->set_title(get_string('commerce_guest_activation_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('commerce_guest_activation_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/guest_account_activation.css'));
$passwordpolicy = [
    'minlength' => max(0, (int)$CFG->minpasswordlength),
    'minlower' => max(0, (int)$CFG->minpasswordlower),
    'minupper' => max(0, (int)$CFG->minpasswordupper),
    'mindigits' => max(0, (int)$CFG->minpassworddigits),
    'minspecial' => max(0, (int)$CFG->minpasswordnonalphanum),
];

$PAGE->requires->js_call_amd(
    'local_subscriptions/guest_account_activation',
    'init',
    [
        get_string('commerce_guest_activation_show_password', 'local_subscriptions'),
        get_string('commerce_guest_activation_hide_password', 'local_subscriptions'),
        $passwordpolicy,
    ]
);

$PAGE->requires->js_init_code(<<<'JS'
window.addEventListener('load', function() {
    const activation = document.getElementById('activation');
    if (!activation) {
        return;
    }

    if (!window.location.hash) {
        window.history.replaceState(null, '', window.location.href + '#activation');
    }

    window.requestAnimationFrame(function() {
        const rect = activation.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const isComfortablyVisible = rect.top >= 72 && rect.top <= viewportHeight * 0.22;
        if (isComfortablyVisible) {
            return;
        }

        activation.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'start'
        });
    });
});
JS
);

$service = new CommerceGuestAccountActivationService(
    $DB,
    new CommerceGuestCheckoutSessionRepository($DB)
);

try {
    $validated = $service->validate($key, $uid, $sessionid);
} catch (Throwable $exception) {
    throw new moodle_exception('commerce_guest_activation_invalid', 'local_subscriptions');
}

$securityrequirements = [];
$securityrequirements[] = get_string(
    'commerce_guest_activation_security_minlength',
    'local_subscriptions',
    (int)$CFG->minpasswordlength
);
if (!empty($CFG->minpasswordlower)) {
    $securityrequirements[] = get_string('commerce_guest_activation_security_lowercase', 'local_subscriptions');
}
if (!empty($CFG->minpasswordupper)) {
    $securityrequirements[] = get_string('commerce_guest_activation_security_uppercase', 'local_subscriptions');
}
if (!empty($CFG->minpassworddigits)) {
    $securityrequirements[] = get_string('commerce_guest_activation_security_digit', 'local_subscriptions');
}
if (!empty($CFG->minpasswordnonalphanum)) {
    $securityrequirements[] = get_string('commerce_guest_activation_security_special', 'local_subscriptions');
}

$form = new CommerceGuestAccountActivationForm(null, [
    'uid' => $uid,
    'sessionid' => $sessionid,
    'key' => $key,
    'reference' => $reference,
    'securityrequirements' => $securityrequirements,
    'passwordpolicy' => $passwordpolicy,
]);

if ($data = $form->get_data()) {
    try {
        $session = $service->complete(
            (string)$data->key,
            (int)$data->uid,
            (int)$data->sessionid,
            (string)$data->password
        );
        $SESSION->local_subscriptions_guest_checkout_token = $session->get_token();
        $returnreference = trim((string)($data->reference ?? $reference));
        $destination = $returnreference !== ''
            ? UrlFactory::order_result([
                'reference' => $returnreference,
                'result' => 'success',
                'accountfinalised' => 1,
            ])
            : UrlFactory::my_campus();
        redirect($destination);
    } catch (Throwable $exception) {
        throw new moodle_exception('commerce_guest_activation_failed', 'local_subscriptions', '', null, $exception->getMessage());
    }
}

$metadata = $validated['session']->get_metadata();
$expiresat = (int)($metadata['activation_link_expires_at'] ?? 0);
$data = [
    'firstname' => format_string((string)$validated['user']->firstname),
    'email' => s((string)$validated['user']->email),
    'expires' => $expiresat > 0 ? userdate($expiresat, get_string('strftimedatetimeshort', 'langconfig')) : '',
];

echo $OUTPUT->header();
echo html_writer::start_div('commerce-guest-activation', ['id' => 'activation', 'tabindex' => '-1']);
echo html_writer::start_div('commerce-guest-activation__card');

echo html_writer::start_div('commerce-guest-activation__header');
echo html_writer::div('🔐', 'commerce-guest-activation__icon', ['aria-hidden' => 'true']);
echo html_writer::tag(
    'h1',
    s(get_string('commerce_guest_activation_title_prefix', 'local_subscriptions')) . ' ' .
        html_writer::span('CampusFR', 'commerce-guest-activation__title-brand'),
    ['class' => 'commerce-guest-activation__title']
);
echo html_writer::tag(
    'p',
    get_string('commerce_guest_activation_intro', 'local_subscriptions', (object)$data),
    ['class' => 'commerce-guest-activation__intro']
);
echo html_writer::div(
    html_writer::span('', 'fa-regular fa-clock', ['aria-hidden' => 'true']) .
        html_writer::span(get_string('commerce_guest_activation_quick_note', 'local_subscriptions')),
    'commerce-guest-activation__quick-note'
);
echo html_writer::end_div();

echo html_writer::start_div('commerce-guest-activation__email');
echo html_writer::div('✉', 'commerce-guest-activation__email-icon', ['aria-hidden' => 'true']);
echo html_writer::start_div('commerce-guest-activation__email-content');
echo html_writer::div(
    get_string('commerce_guest_activation_email_label', 'local_subscriptions'),
    'commerce-guest-activation__email-label'
);
echo html_writer::div($data['email'], 'commerce-guest-activation__email-address');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div('', 'commerce-guest-activation__divider', ['aria-hidden' => 'true']);
echo html_writer::start_div('commerce-guest-activation__form');
$form->display();
echo html_writer::end_div();

echo html_writer::div('', 'commerce-guest-activation__divider commerce-guest-activation__divider--footer', [
    'aria-hidden' => 'true',
]);
echo html_writer::start_div('commerce-guest-activation__secure-note');
echo html_writer::div('', 'commerce-guest-activation__secure-note-icon fa-solid fa-shield-halved', [
    'aria-hidden' => 'true',
]);
echo html_writer::start_div('commerce-guest-activation__secure-note-content');
echo html_writer::div(
    get_string('commerce_guest_activation_protected_title', 'local_subscriptions'),
    'commerce-guest-activation__secure-note-title'
);
echo html_writer::div(
    get_string('commerce_guest_activation_protected_text', 'local_subscriptions'),
    'commerce-guest-activation__secure-note-text'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
