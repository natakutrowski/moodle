<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\commerce\gift\CommerceGiftDevE2EGate;
use local_subscriptions\commerce\gift\CommerceGiftCommercialDevGate;
use local_subscriptions\commerce\gift\CommerceGiftCommercialRuntimeGate;
use local_subscriptions\commerce\gift\CommerceGiftFeatureToggle;
use local_subscriptions\commerce\gift\CommerceGiftClaimService;
use local_subscriptions\commerce\gift\CommerceGiftReaderActivationService;
use local_subscriptions\commerce\gift\CommerceGiftSchema;

// G6B5: still DEV-only. The public gift runtime remains explicitly unwired.
if (!CommerceGiftCommercialRuntimeGate::can_operate()
    || !(new CommerceGiftFeatureToggle())->can_redeem()) {
    throw new moodle_exception('nopermissions');
}
header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');

$claimurl = new moodle_url('/local/subscriptions/gift_claim.php');
$loggedin = isloggedin() && !isguestuser() && (int)($USER->id ?? 0) > 2;
$signin = optional_param('signin', 0, PARAM_BOOL) === 1;

// Explicit sign-in step, preserving Moodle's normal wantsurl/SSO mechanism.
// Never carry the invitation code in a query parameter or session redirect.
if (!$loggedin && ($signin || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
    // Explicit Moodle login handoff. Preserve only the safe claim PAGE URL,
    // never a raw invitation code; avoid require_login() before PAGE->set_url().
    $SESSION->wantsurl = $claimurl->out(false);
    redirect(get_login_url());
}
if ($loggedin) {
    require_login();
    if ($signin) {
        redirect($claimurl);
    }
}

$PAGE->set_context($loggedin ? context_user::instance((int)$USER->id) : context_system::instance());
$PAGE->set_url($claimurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('cfr_gift_g4_claim_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('cfr_gift_g4_claim_title', 'local_subscriptions'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/gifts_g4.css'));
$g4 = static fn(string $key): string => get_string($key, 'local_subscriptions');

// A visitor clicking their email link must see why they cannot claim yet.
// The public/PROD route is still unavailable, and no code can be submitted while OFF.
$available = CommerceGiftCommercialRuntimeGate::can_operate()
    && (new CommerceGiftFeatureToggle())->can_redeem();
if ($loggedin && $available) {
    if (!CommerceGiftCommercialRuntimeGate::enabled() && !CommerceGiftCommercialDevGate::is_armed()) {
        CommerceGiftDevE2EGate::assert_test_account((int)$USER->id, 'recipient');
    }
    if (empty($USER->confirmed) || !empty($USER->suspended) || is_siteadmin((int)$USER->id)) {
        throw new moodle_exception('nopermissions');
    }
}

$noticekey = '';
$pendingid = 0;
if ($loggedin && $available && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_sesskey();
    $action = required_param('gift_action', PARAM_ALPHA);
    if ($action === 'claim') {
        $rawtoken = required_param('token', PARAM_RAW_TRIMMED);
        try {
            $gift = (new CommerceGiftClaimService($DB))->claim($rawtoken);
            $pendingid = (int)$gift->id;
            (new CommerceGiftReaderActivationService($DB))->activate($pendingid);
            $noticekey = 'cfr_gift_g4_claim_success';
            $pendingid = 0;
        } catch (\Throwable $e) {
            // No detailed reason, token, recipient identity or payment status in HTML.
            $noticekey = 'cfr_gift_g4_claim_unavailable';
        }
    } elseif ($action === 'retry') {
        $pendingid = required_param('giftid', PARAM_INT);
        $candidate = $DB->get_record(CommerceGiftSchema::TABLE_GIFT,
            ['id' => $pendingid, 'intendedrecipientuserid' => (int)$USER->id], '*', IGNORE_MISSING);
        if (!$candidate || !in_array((string)$candidate->status,
            ['claimed', 'grant_pending', 'grant_failed'], true)) {
            throw new moodle_exception('nopermissions');
        }
        try {
            (new CommerceGiftReaderActivationService($DB))->activate($pendingid);
            $noticekey = 'cfr_gift_g4_claim_success';
            $pendingid = 0;
        } catch (\Throwable $e) {
            $noticekey = 'cfr_gift_g4_claim_retry_later';
        }
    } else {
        throw new moodle_exception('invalidparameter');
    }
}
if ($loggedin && $available && $pendingid === 0) {
    $pending = $DB->get_records_select(CommerceGiftSchema::TABLE_GIFT,
        'intendedrecipientuserid = :userid AND status IN (:s1,:s2,:s3)',
        ['userid' => (int)$USER->id, 's1'=>'claimed', 's2'=>'grant_pending', 's3'=>'grant_failed'],
        'timecreated DESC', 'id', 0, 1);
    if ($pending) {
        $pendingid = (int)reset($pending)->id;
    }
}

echo $OUTPUT->header();
echo html_writer::start_div('container py-4 cfr-gift-g4 cfr-gift-g6b5');
echo html_writer::tag('h1', s($g4('cfr_gift_g4_claim_title')), ['class' => 'h2']);
if (!$available) {
    echo $OUTPUT->notification(s($g4('cfr_gift_g6b5_unavailable_now')), 'info');
} elseif (!$loggedin) {
    echo html_writer::start_div('cfr-gift-g6b5__sign-in');
    echo html_writer::div('🎁', 'cfr-gift-g6b5__icon', ['aria-hidden' => 'true']);
    echo html_writer::tag('h2', s($g4('cfr_gift_g6b5_signin_title')), ['class' => 'h4']);
    echo html_writer::tag('p', s($g4('cfr_gift_g6b5_signin_intro')));
    echo html_writer::link(new moodle_url('/local/subscriptions/gift_claim.php', ['signin' => 1]),
        s($g4('cfr_gift_g6b5_signin_button')), ['class' => 'btn btn-primary cfr-gift-g6b5__cta']);
    echo html_writer::tag('p', s($g4('cfr_gift_g6b5_no_account')), ['class' => 'cfr-gift-g6b5__secondary']);
    echo html_writer::link(new moodle_url('/local/subscriptions/gift_recipient_welcome.php'),
        s($g4('cfr_gift_g6b5_create_account')), ['class' => 'cfr-gift-g6b5__secondary-link']);
    echo html_writer::tag('p', s($g4('cfr_gift_g6b5_privacy')), ['class' => 'cfr-gift-g6b5__privacy']);
    echo html_writer::end_div();
} else {
    echo html_writer::tag('p', s($g4('cfr_gift_g6b5_signed_in_hint')), ['class' => 'mb-2']);
    echo html_writer::start_div('cfr-gift-g6b5__identity');
    echo html_writer::tag('span', s($g4('cfr_gift_g6b5_signed_in_as')), ['class' => 'cfr-gift-g6b5__identity-label']);
    echo html_writer::tag('strong', s(fullname($USER)) . ' · ' . s((string)$USER->email),
        ['class' => 'cfr-gift-g6b5__identity-value']);
    echo html_writer::link(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]),
        s($g4('cfr_gift_g6b5_change_account')), ['class' => 'cfr-gift-g6b5__secondary-link']);
    echo html_writer::end_div();

    if ($noticekey === 'cfr_gift_g4_claim_success') {
        echo html_writer::start_div('alert alert-success cfr-gift-g6b5__success', ['role' => 'status']);
        echo html_writer::tag('p', s($g4($noticekey)), ['class' => 'fw-bold mb-3']);
        echo html_writer::link(new moodle_url('/local/campusreader/my.php'),
            '📚 ' . s($g4('cfr_gift_g6b5_library_button')),
            ['class' => 'btn btn-primary cfr-gift-g6b5__cta']);
        echo html_writer::end_div();
    } else {
        echo html_writer::tag('p', s($g4('cfr_gift_g4_claim_intro')), ['class' => 'text-muted']);
        if ($noticekey !== '') {
            echo $OUTPUT->notification(s($g4($noticekey)), 'warning');
        }
        if ($pendingid > 0) {
            echo html_writer::start_tag('form', ['method' => 'post', 'action' => $claimurl->out(false)]);
            foreach (['sesskey' => sesskey(), 'gift_action' => 'retry', 'giftid' => $pendingid] as $k => $v) {
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $k, 'value' => $v]);
            }
            echo html_writer::tag('button', s($g4('cfr_gift_g4_retry_activation')),
                ['type' => 'submit', 'class' => 'btn btn-outline-primary']);
            echo html_writer::end_tag('form');
        }
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $claimurl->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'gift_action', 'value' => 'claim']);
        echo html_writer::tag('label', s($g4('cfr_gift_g4_token_label')),
            ['for' => 'cfr_gift_token', 'class' => 'form-label']);
        echo html_writer::empty_tag('input', ['type' => 'password', 'name' => 'token', 'id' => 'cfr_gift_token',
            'required' => 'required', 'maxlength' => '43', 'autocomplete' => 'off',
            'class' => 'form-control mb-3']);
        echo html_writer::tag('button', s($g4('cfr_gift_g4_claim_button')),
            ['type' => 'submit', 'class' => 'btn btn-primary']);
        echo html_writer::end_tag('form');
    }
    echo html_writer::link(new moodle_url('/local/subscriptions/my_gifts.php'),
        s($g4('cfr_gift_g4_my_gifts')), ['class' => 'btn btn-link mt-3']);
}
echo html_writer::end_div();
echo $OUTPUT->footer();
