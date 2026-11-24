<?php
// G5B3B DEV: verified recipient signup; the public gift runtime remains hard-disabled.
require_once(__DIR__ . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\gift\CommerceGiftDevE2EGate;
use local_subscriptions\commerce\gift\CommerceGiftRecipientSignupService;

if (!\local_subscriptions\commerce\gift\CommerceGiftCommercialRuntimeGate::can_operate()
    || !(new \local_subscriptions\commerce\gift\CommerceGiftFeatureToggle())->can_redeem()) {
    throw new moodle_exception('nopermissions');
}
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/gift_recipient_welcome.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cfr_gift_onboard_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('cfr_gift_onboard_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/gift_recipient_onboarding.css'));
// Same live Moodle password policy and interactive criteria as guest_account_activate.php.
$passwordpolicy = [
    'minlength' => max(0, (int)$CFG->minpasswordlength),
    'minlower' => max(0, (int)$CFG->minpasswordlower),
    'minupper' => max(0, (int)$CFG->minpasswordupper),
    'mindigits' => max(0, (int)$CFG->minpassworddigits),
    'minspecial' => max(0, (int)$CFG->minpasswordnonalphanum),
];
$PAGE->requires->js_call_amd('local_subscriptions/guest_account_activation', 'init', [
    get_string('commerce_guest_activation_show_password', 'local_subscriptions'),
    get_string('commerce_guest_activation_hide_password', 'local_subscriptions'),
    $passwordpolicy,
]);
$g = static fn(string $key) => get_string('cfr_gift_signup_' . $key, 'local_subscriptions');
$e = static fn(string $s): string => s($s);
$service = new CommerceGiftRecipientSignupService($DB);
$state = null;
$notice = '';
$error = '';
$demo = optional_param('demo', 0, PARAM_BOOL) === 1 && is_siteadmin();
$complete = false;

if ($demo) {
    $state = ['giftid' => 0, 'email' => 'camille@example.com', 'account_exists' => false, 'allow_registration' => true];
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    $action = required_param('gift_action', PARAM_ALPHA);
    try {
        if ($action === 'inspect') {
            $state = $service->begin(required_param('giftcode', PARAM_RAW_TRIMMED));
        } elseif ($action === 'send') {
            $service->send_email_verification();
            $notice = 'sent';
        } elseif ($action === 'register') {
            $result = $service->verify_create_and_claim(
                required_param('emailcode', PARAM_RAW_TRIMMED),
                required_param('firstname', PARAM_TEXT), required_param('lastname', PARAM_TEXT),
                required_param('password', PARAM_RAW), required_param('passwordconfirm', PARAM_RAW)
            );
            $complete = $result['activated'];
            $notice = $complete ? 'success' : 'pending';
        } elseif ($action === 'existing') {
            $complete = $service->claim_existing();
            $notice = $complete ? 'success' : 'pending';
        } else {
            throw new \InvalidArgumentException('Unexpected gift action.');
        }
    } catch (\Throwable $ex) {
        // Error details, tokens, identities and mail credentials never go to HTML or logs.
        $error = 'unavailable';
    }
}
if (!$demo && !$complete) {
    try { $state = $service->current(); }
    catch (\Throwable $ignored) { $state = null; }
}
$actionurl = (new moodle_url('/local/subscriptions/gift_recipient_welcome.php'))->out(false);
$startform = static function(string $action) use ($actionurl): void {
    echo html_writer::start_tag('form', ['action' => $actionurl, 'method' => 'post', 'autocomplete' => 'off']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'gift_action', 'value' => $action]);
};
$input = static function(string $id, string $label, string $type = 'text', bool $disabled = false) use ($e): void {
    // Keep the same input IDs used by the existing, certified guest password widget.
    $fieldid = in_array($id, ['password', 'passwordconfirm'], true) ? 'id_' . $id : 'cfr-gift-' . $id;
    echo html_writer::start_tag('label', ['for' => $fieldid]);
    echo $e($label);
    $attrs = ['id' => $fieldid, 'name' => $id,
        'type' => $type, 'required' => 'required', 'class' => 'form-control',
        'maxlength' => ($id === 'emailcode' ? 6 : ($type === 'password' ? 128 : 80)),
        'autocomplete' => $type === 'password' ? 'new-password' : 'off'];
    if ($id === 'emailcode') {
        $attrs['inputmode'] = 'numeric';
        $attrs['pattern'] = '[0-9]{6}';
        $attrs['minlength'] = 6;
        $attrs['title'] = $e(get_string('cfr_gift_password_otp_digits', 'local_subscriptions'));
    }
    if ($disabled) { $attrs['disabled'] = 'disabled'; }
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::end_tag('label');
};
echo $OUTPUT->header();
echo html_writer::start_div('cfr-gift-onboard');
echo html_writer::start_div('cfr-gift-onboard__card');
echo html_writer::div('🎁', 'cfr-gift-onboard__icon', ['aria-hidden' => 'true']);
echo html_writer::tag('h1', $e($g('title')), ['class' => 'cfr-gift-onboard__title']);
echo html_writer::tag('p', $e($g('intro')), ['class' => 'cfr-gift-onboard__intro']);
if ($demo) { echo html_writer::div($e($g('demo')), 'cfr-gift-onboard__warning'); }
if ($error) { echo $OUTPUT->notification($e($g('unavailable')), 'warning'); }
if ($notice) { echo $OUTPUT->notification($e($g($notice)), $complete ? 'success' : 'info'); }
if ($complete) {
    echo html_writer::link(new moodle_url('/local/campusreader/my.php'), $e($g('library')),
        ['class' => 'btn btn-primary cfr-gift-onboard__button']);
} elseif (!$state) {
    $startform('inspect');
    $input('giftcode', $g('code'), 'password');
    echo html_writer::tag('button', $e($g('verify')), ['type' => 'submit', 'class' => 'btn btn-primary cfr-gift-onboard__button']);
    echo html_writer::end_tag('form');
} else {
    echo html_writer::div($e($g('email')), 'cfr-gift-onboard__email-label');
    echo html_writer::div($e((string)$state['email']), 'cfr-gift-onboard__email');
    if ($state['account_exists'] || !$state['allow_registration']) {
        echo html_writer::tag('p', $e($g('existing')));
        if (isloggedin() && !isguestuser() && !$demo) {
            $startform('existing');
            echo html_writer::tag('button', $e($g('existing_claim')), ['type' => 'submit', 'class' => 'btn btn-primary cfr-gift-onboard__button']);
            echo html_writer::end_tag('form');
        } else {
            echo html_writer::link(new moodle_url('/login/index.php'), $e($g('login')),
                ['class' => 'btn btn-primary cfr-gift-onboard__button']);
            echo html_writer::tag('p', $e($g('login_hint')), ['class' => 'cfr-gift-onboard__footnote']);
        }
    } else {
        echo html_writer::tag('p', $e($g('new')));
        if (!$demo) {
            $startform('send');
            echo html_writer::tag('button', $e($g('send')), ['type' => 'submit', 'class' => 'btn btn-outline-primary']);
            echo html_writer::end_tag('form');
        }
        $startform('register');
        echo html_writer::start_div('cfr-gift-onboard__grid');
        $input('firstname', $g('firstname'), 'text', $demo);
        $input('lastname', $g('lastname'), 'text', $demo);
        $input('password', $g('password'), 'password', $demo);
        $input('passwordconfirm', $g('passwordconfirm'), 'password', $demo);
        echo html_writer::end_div();
        // Only show conditions actually configured in Moodle; check_password_policy()
        // remains the server-side authority, so JavaScript cannot weaken validation.
        $rules = [
            ['minlength', get_string('commerce_guest_activation_security_minlength', 'local_subscriptions', $passwordpolicy['minlength'])],
        ];
        foreach ([
            'minlower' => ['lowercase', 'commerce_guest_activation_security_lowercase'],
            'minupper' => ['uppercase', 'commerce_guest_activation_security_uppercase'],
            'mindigits' => ['digit', 'commerce_guest_activation_security_digit'],
            'minspecial' => ['special', 'commerce_guest_activation_security_special'],
        ] as $setting => [$name, $labelkey]) {
            if ($passwordpolicy[$setting] > 0) {
                $rules[] = [$name, get_string($labelkey, 'local_subscriptions')];
            }
        }
        $rules[] = ['match', get_string('commerce_guest_activation_security_match', 'local_subscriptions')];
        $items = '';
        foreach ($rules as [$name, $label]) {
            $items .= html_writer::tag('li',
                html_writer::span('•', 'commerce-guest-activation__requirement-icon', ['aria-hidden' => 'true'])
                . html_writer::span($e($label)),
                ['class' => 'cfr-gift-onboard__requirement', 'data-password-rule' => $name]);
        }
        echo html_writer::start_div('cfr-gift-onboard__security');
        echo html_writer::tag('h2', '🔒 ' . $e(get_string('commerce_guest_activation_security_title', 'local_subscriptions')),
            ['class' => 'cfr-gift-onboard__security-title']);
        echo html_writer::tag('ul', $items, ['class' => 'cfr-gift-onboard__requirements', 'aria-live' => 'polite']);
        echo html_writer::end_div();
        $input('emailcode', $g('emailcode'), 'text', $demo);
        echo html_writer::tag('p', $e($g('safety')), ['class' => 'cfr-gift-onboard__footnote']);
        $submitattrs = ['type' => 'submit', 'class' => 'btn btn-primary cfr-gift-onboard__button'];
        if ($demo) { $submitattrs['disabled'] = 'disabled'; }
        echo html_writer::tag('button', $e($g('create')), $submitattrs);
        echo html_writer::end_tag('form');
    }
}
echo html_writer::end_div();
echo html_writer::end_div();
echo $OUTPUT->footer();
