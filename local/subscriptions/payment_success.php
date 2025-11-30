<?php
require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

\local_subscriptions\subscription_config::guard_public_access();

// --- Helper format prix (respecte l’option symbole) ---
function ls_money_fmt(float $amount, string $cur): string {
    $usesymbol = (bool) get_config('local_subscriptions','display_currency_symbols');
    $amt = number_format($amount, 2, '.', '');
    $cur = strtoupper($cur ?: 'EUR');
    $sym = ($cur === 'EUR') ? '€' : (($cur === 'RUB' || $cur === 'RUR') ? '₽' : $cur);
    return $usesymbol ? ($amt.' '.$sym) : ($amt.' '.$cur);
}

$prid       = required_param('pid', PARAM_INT);
$session_id = optional_param('session_id', '', PARAM_RAW_TRIMMED);
$token      = optional_param('t', '', PARAM_ALPHANUMEXT);
$lang = optional_param('lang','', PARAM_ALPHANUMEXT);
if ($lang !== '') {
    // Ne pas utiliser force_current_language() ici
    $SESSION->lang = $lang;   // langue de session "normale", modifiable via le sélecteur
    moodle_setlocale();       // réinitialise locale/strings pour cette requête
}

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
        $user = $DB->get_recorD('user', ['email' => core_text::strtolower($pr->email), 'deleted' => 0], '*', IGNORE_MISSING);
        if ($user) {
            require_once($CFG->dirroot.'/lib/classes/session/manager.php');
            require_once($CFG->dirroot.'/user/lib.php');

            // Invalider le token à usage unique
            $pr->login_token = null;
            $pr->login_token_expires = null;
            $pr->last_update = time();
            $DB->update_record('subscription_payment_request', $pr);

            // 🔐 Par sécurité, si un flag de change-password reste accroché, on le nettoie
            if (!empty($user->forcepasswordchange)) {
                $user->forcepasswordchange = 0;
                user_update_user($user, false);
            }
            unset_user_preference('auth_forcepasswordchange', $user->id);

            // Connexion directe, sans forcer un changement de mot de passe
            \core\session\manager::login_user($user);
        }
    }
}


echo $OUTPUT->header();

$support  = get_config('local_subscriptions', 'support_email') ?: 'support@campusfr.fr';
$orderref = get_string('payui_order_ref', 'local_subscriptions', $prid);

$amountHtml = '';
$planHtml   = '';

$successimg    = new moodle_url('/local/subscriptions/pix/payment_success.png');
$mycoursesUrl  = new \moodle_url('/local/campus/mycourses.php');
$mycoursesLbl  = get_string('payui_cta_mycourses','local_subscriptions');
$contactLbl    = get_string('payui_cta_contact','local_subscriptions');

// Pour le compte à rebours
$do_redirect      = false;
$redirectDelay    = 30;
$redirectTemplate = '';


// Bloc PR → lire le plan (inchangé)
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

// --- Montant payé (priorité Sub) | fallback LOCK | sinon PR.price ---
if ($pr) {
    $cur     = strtoupper($pr->currency ?? 'EUR');
    $paid    = null;   // montant final (affiché)
    $list    = 0.0;    // prix catalogue pour affichage barré (si connu)
    $discPct = 0;      // % remise
    $reason  = null;   // raison remise (ex. 'trial72h')

    // 1) Sub liée → vérité de ce qui a été payé
    if (!empty($pr->subscriptionid)) {
        $sub = $DB->get_record('user_subscription', ['id'=>$pr->subscriptionid], '*', IGNORE_MISSING);
        if ($sub) {
            $paid    = (float)$sub->pricepaid;
            $cur     = strtoupper($sub->currency ?: $cur);
            $discPct = (int)($sub->discount_percent ?? 0);
            $reason  = $sub->discount_reason ?? null;
            if ($discPct > 0) {
                $list = $paid + (float)($sub->discount_amount ?? 0.0);
            }
        }
    }

    // 2) Fallback : LOCK ou amount_minor
    if ($paid === null) {
        if (!empty($pr->amount_minor)) {
            $paid = ((int)$pr->amount_minor) / 100.0;
        } else if (!empty($pr->locked_final_price) && (float)$pr->locked_final_price > 0) {
            $paid = (float)$pr->locked_final_price;
        } else if (isset($pr->price)) {
            $paid = (float)$pr->price;
        }
        $discPct = (int)($pr->locked_discount_percent ?? 0);
        $reason  = $pr->locked_discount_reason ?? null;
        if ($discPct > 0 && !empty($pr->locked_list_price)) {
            $list = (float)$pr->locked_list_price;
        }
    }

    // 3) Rendu : label + barré/vert si remise, sinon simple
    if ($paid !== null) {
        $label = get_string('payui_label_price','local_subscriptions'); // ex. "Prix" / "Montant"
        $amountHtml .= html_writer::div($label.' :', 'text-muted small mb-1');

        if ($discPct > 0 && $list > 0 && $paid < $list) {
            $amountHtml .= html_writer::start_div('fs-5');
            $amountHtml .= html_writer::tag('span', ls_money_fmt($list, $cur),
                ['class'=>'text-muted text-decoration-line-through me-2']);
            $amountHtml .= html_writer::tag('span',
                ls_money_fmt($paid, $cur) . ' ' . html_writer::tag('small', '(-'.$discPct.'%)', ['class'=>'ms-1']),
                ['class'=>'fw-semibold text-success']);
            $amountHtml .= html_writer::end_div();

            // Message de raison (optionnel si la chaîne existe)
            if (function_exists('get_string_manager')
                && get_string_manager()->string_exists('reason_trial72h','local_subscriptions')
                && $reason === 'trial72h') {
                $amountHtml .= html_writer::div(
                    get_string('reason_trial72h','local_subscriptions', $discPct),
                    'small text-muted'
                );
            }
        } else {
            $amountHtml .= html_writer::div(ls_money_fmt($paid, $cur), 'fs-5 fw-semibold');
        }
    }
}


$mycoursesUrl = new \moodle_url('/local/campus/mycourses.php');
$mycoursesLbl = get_string('payui_cta_mycourses','local_subscriptions');
$contactLbl = get_string('payui_cta_contact','local_subscriptions');

if ($pr && in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true)) {
    echo html_writer::start_div('container my-4');

    // Carte principale
    echo html_writer::start_div('card shadow-sm payment-success-card');
    echo html_writer::div(
        html_writer::tag('h2', get_string('payui_success_title','local_subscriptions'), ['class'=>'h4 m-0']),
        'card-header bg-light'
    );

    echo html_writer::start_div('card-body');
    echo html_writer::start_div('row align-items-center');

    // Colonne texte (gauche)
    echo html_writer::start_div('col-md-7 mb-3 mb-md-0');

    echo html_writer::tag('p', get_string('payui_success_subtitle','local_subscriptions'), ['class'=>'text-muted mb-2']);
    echo html_writer::tag('p', get_string('payui_success_thanks','local_subscriptions'),   ['class'=>'mb-3']);
    echo html_writer::div(html_writer::span(s($orderref), 'small text-muted'), 'mb-3');

    echo $amountHtml;
    echo $planHtml;

    if (isloggedin() && !isguestuser()) {
        echo html_writer::start_div('d-flex flex-wrap gap-2 mt-3');
        echo html_writer::link($mycoursesUrl, $mycoursesLbl, ['class'=>'btn btn-primary']);
        echo html_writer::link(
            UrlFactory::my_subscriptions(),
            get_string('payui_cta_my_subscriptions','local_subscriptions'),
            ['class'=>'btn btn-outline-secondary']
        );
        echo html_writer::link(
            new moodle_url('mailto:'.$support, ['subject'=>'Payment success #'.$prid]),
            $contactLbl,
            ['class'=>'btn btn-link']
        );
        echo html_writer::end_div();

        // Texte de redirection automatique (seulement pour les utilisateurs connectés)
        $redirectTemplate = get_string('paymentsuccess_redirect_msg', 'local_subscriptions', '__SECONDS__');
        echo html_writer::div(
            html_writer::span(
                str_replace('__SECONDS__', $redirectDelay, $redirectTemplate),
                '',
                ['id' => 'paymentsuccess-redirect-text']
            ),
            'mt-3 text-muted small'
        );
        $do_redirect = true;

    } else {
        // Invité : proposer connexion (comme avant)
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

        echo html_writer::start_div('d-flex flex-wrap gap-2 mt-2');
        echo html_writer::link($loginurl, get_string('payui_cta_signin','local_subscriptions'), ['class'=>'btn btn-primary']);
        echo html_writer::link($mycoursesUrl, $mycoursesLbl, ['class'=>'btn btn-outline-secondary']);
        echo html_writer::link(
            new moodle_url('mailto:'.$support, ['subject'=>'Payment success (guest) #'.$prid]),
            $contactLbl,
            ['class'=>'btn btn-link']
        );
        echo html_writer::end_div();
    }

    echo html_writer::div(
        get_string('payui_support_hint','local_subscriptions',$support),
        'text-muted small mt-3'
    );

    echo html_writer::end_div(); // col texte

    // Colonne image (droite)
    echo html_writer::start_div('col-md-5 text-center');
    echo html_writer::start_div('payment-status-illustration-wrapper');
    echo html_writer::empty_tag('img', [
        'src'   => $successimg->out(false),
        'alt'   => get_string('paymentsuccess_mascot_alt', 'local_subscriptions'),
        'class' => 'img-fluid payment-status-illustration payment-success-giraffe'
    ]);
    echo html_writer::end_div(); // wrapper
    echo html_writer::end_div(); // col image

    echo html_writer::end_div(); // row
    echo html_writer::end_div(); // card-body
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

// Compte à rebours + confettis uniquement si paiement confirmé ET utilisateur connecté
if (!empty($do_redirect)) {
    echo html_writer::script("
    (function(){
        var total   = {$redirectDelay};
        var span    = document.getElementById('paymentsuccess-redirect-text');
        var base    = ".json_encode($redirectTemplate).";
        var target  = ".json_encode($mycoursesUrl->out(false)).";
        if (!span) { return; }

        function update() {
            span.textContent = base.replace('__SECONDS__', total);
        }
        update();

        var timer = setInterval(function(){
            total--;
            if (total <= 0) {
                clearInterval(timer);
                window.location.href = target;
                return;
            }
            update();
        }, 1000);

        // Mini feu d'artifice / confettis
        function createContainer() {
            var c = document.createElement('div');
            c.className = 'payment-fireworks';
            document.body.appendChild(c);
            return c;
        }

        function burst(container, x, y) {
            var count = 24;
            for (var i = 0; i < count; i++) {
                var s = document.createElement('div');
                s.className = 'spark' + (i % 2 ? ' alt' : '');
                s.style.left = x + 'px';
                s.style.top  = y + 'px';
                var angle = Math.random() * 2 * Math.PI;
                var dist  = 80 + Math.random() * 80;
                s.style.setProperty('--dx', (Math.cos(angle) * dist) + 'px');
                s.style.setProperty('--dy', (Math.sin(angle) * dist) + 'px');
                container.appendChild(s);
                (function(el) {
                    setTimeout(function() {
                        if (el.parentNode) {
                            el.parentNode.removeChild(el);
                        }
                    }, 800);
                })(s);
            }
        }

        window.addEventListener('load', function() {
            var container = createContainer();
            var w = window.innerWidth;
            var h = window.innerHeight;

            setTimeout(function() { burst(container, w * 0.3, h * 0.35); }, 200);
            setTimeout(function() { burst(container, w * 0.7, h * 0.30); }, 500);
            setTimeout(function() { burst(container, w * 0.5, h * 0.55); }, 800);

            setTimeout(function() {
                if (container.parentNode) {
                    container.parentNode.removeChild(container);
                }
            }, 1600);
        });
    })();
    ");
}


echo $OUTPUT->footer();
