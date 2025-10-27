<?php
require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

\local_subscriptions\subscription_config::guard_public_access();

$prid       = required_param('pid', PARAM_INT);
$session_id = optional_param('session_id', '', PARAM_RAW_TRIMMED);
$token      = optional_param('t', '', PARAM_ALPHANUMEXT);

global $DB, $CFG, $SITE, $OUTPUT;

$PAGE->set_url(UrlFactory::payment_success(['pid' => $prid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payui_success_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

$pr = $DB->get_record('subscription_payment_request', ['id' => $prid], '*', IGNORE_MISSING);

// Auto-login sécurisé (token usage unique)
if ($pr && in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true) && (!isloggedin() || isguestuser())) {
    $tokvalid = !empty($token) && !empty($pr->login_token)
        && hash_equals((string)$pr->login_token, (string)$token)
        && !empty($pr->login_token_expires) && (int)$pr->login_token_expires >= time();

    if ($tokvalid && !empty($pr->email)) {
        $user = $DB->get_record('user', ['email' => core_text::strtolower($pr->email), 'deleted' => 0], '*', IGNORE_MISSING);
        if ($user) {
            require_once($CFG->dirroot.'/lib/classes/session/manager.php');
            require_once($CFG->dirroot.'/user/lib.php');

            $pr->login_token = null;
            $pr->login_token_expires = null;
            $pr->last_update = time();
            $DB->update_record('subscription_payment_request', $pr);

            $needchangepw = !empty($user->forcepasswordchange);

            \core\session\manager::login_user($user);

            if ($needchangepw) {
                // Après changement MDP → mes cours
                $return   = new \moodle_url('/local/campus/mycourses.php');
                $changepw = new \moodle_url('/login/change_password.php', ['returnurl' => $return->out(false)]);
                redirect($changepw);
            } else {
                redirect(new \moodle_url('/local/campus/mycourses.php'));
            }
        }
    }
}

echo $OUTPUT->header();

$support  = get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr';
$orderref = get_string('payui_order_ref', 'local_subscriptions', $prid);

$amountHtml = '';
$planHtml   = '';
if ($pr && isset($pr->price, $pr->currency)) {
    $amountHtml = html_writer::div(
        html_writer::span(get_string('payui_label_price','local_subscriptions').': ', 'text-muted').
        html_writer::span(format_float((float)$pr->price,2).' '.s(strtoupper($pr->currency)), 'fw-semibold'),
        'mb-2'
    );
}
if ($pr && !empty($pr->planid)) {
    $planname = $DB->get_field('subscription_plan', 'name', ['id'=>(int)$pr->planid], IGNORE_MISSING) ?: '';
    if ($planname !== '') {
        $planHtml = html_writer::div(
            html_writer::span(get_string('payui_label_plan','local_subscriptions').': ', 'text-muted').
            html_writer::span(format_string($planname), 'fw-semibold'),
            'mb-2'
        );
    }
}

$mycoursesUrl = new \moodle_url('/local/campus/mycourses.php');
$mycoursesLbl = get_string('payui_cta_mycourses','local_subscriptions');
$contactLbl = get_string('payui_cta_contact','local_subscriptions');

if ($pr && in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true)) {
    echo html_writer::start_div('container my-4');
    echo html_writer::start_div('card shadow-sm');
    echo html_writer::div(html_writer::tag('h2', get_string('payui_success_title','local_subscriptions'), ['class'=>'h4 m-0']), 'card-header bg-light');

    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('payui_success_subtitle','local_subscriptions'), ['class'=>'text-muted mb-2']);
    echo html_writer::tag('p', get_string('payui_success_thanks','local_subscriptions'),   ['class'=>'mb-3']);
    echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');

    echo $amountHtml;
    echo $planHtml;

    if (isloggedin() && !isguestuser()) {
        echo html_writer::start_div('d-flex gap-2 mt-3');
        echo html_writer::link(UrlFactory::my_subscriptions(), get_string('payui_cta_my_subscriptions','local_subscriptions'), ['class'=>'btn btn-primary']);
        echo html_writer::link($mycoursesUrl, $mycoursesLbl, ['class'=>'btn btn-outline-secondary']);
        echo html_writer::link(
            new moodle_url('mailto:'.$support, ['subject'=>'Payment success #'.$prid]),
            $contactLbl,
            ['class'=>'btn btn-link']
        );
        echo html_writer::end_div();
    } else {
        // Invité : proposer connexion
        $email    = $pr->email ?? '';
        $username = '';
        if (!empty($pr->userid)) {
            $u = $DB->get_record('user', ['id'=>$pr->userid, 'deleted'=>0], 'id,username', IGNORE_MISSING);
            if ($u) { $username = (string)$u->username; }
        }
        if ($username === '' && $email !== '') {
            $u = $DB->get_record('user', ['email'=>core_text::strtolower($email), 'deleted'=>0], 'id,username', IGNORE_MISSING);
            if ($u) { $username = (string)$u->username; }
        }
        if ($username === '') {
            $username = function_exists('local_subscriptions_generate_unique_username')
                ? local_subscriptions_generate_unique_username($pr->firstname ?? '', $pr->lastname ?? '', $email ?? '')
                : ($email ?: '');
        }

        $loginurl = new moodle_url('/login/index.php', ['username'=>$username]);

        echo html_writer::div(get_string('payui_success_check_email','local_subscriptions'), 'alert alert-info my-3');

        echo html_writer::start_div('d-flex gap-2 mt-2');
        echo html_writer::link($loginurl, get_string('payui_cta_signin','local_subscriptions'), ['class'=>'btn btn-primary']);
        echo html_writer::link($mycoursesUrl, $mycoursesLbl, ['class'=>'btn btn-outline-secondary']);
        echo html_writer::link(
            new moodle_url('mailto:'.$support, ['subject'=>'Payment success (guest) #'.$prid]),
            $contactLbl,
            ['class'=>'btn btn-link']
        );
        echo html_writer::end_div();
    }

    echo html_writer::div(get_string('payui_support_hint','local_subscriptions',$support), 'text-muted small mt-3');

    echo html_writer::end_div(); // body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // container

} else {
    // En attente (webhook pas encore passé)
    echo html_writer::start_div('container my-4');
    echo html_writer::start_div('card shadow-sm');
    echo html_writer::div(html_writer::tag('h2', get_string('payui_pending_title','local_subscriptions'), ['class'=>'h4 m-0']), 'card-header bg-light');

    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('payui_pending_msg','local_subscriptions'), ['class'=>'mb-3']);
    echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');

    if (!empty($session_id) && (is_siteadmin() || !empty($CFG->debugdeveloper))) {
        echo html_writer::div(
            html_writer::span(get_string('payui_session_display','local_subscriptions',$session_id), 'small text-muted'),
            'mb-3'
        );
    }

    echo html_writer::start_div('d-flex gap-2 mt-2');
    echo html_writer::link($mycoursesUrl, $mycoursesLbl, ['class'=>'btn btn-outline-secondary']);
    echo html_writer::link(
        new moodle_url('mailto:'.$support, ['subject'=>'Payment pending #'.$prid]),
        get_string('payui_cta_contact','local_subscriptions'),
        ['class'=>'btn btn-link']
    );
    echo html_writer::end_div();

    echo html_writer::end_div(); // body
    echo html_writer::end_div(); // card
    echo html_writer::end_div(); // container
}

echo $OUTPUT->footer();
