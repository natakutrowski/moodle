<?php

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;
use local_subscriptions\constants\Status;

$prid       = required_param('pid', PARAM_INT);
$session_id = optional_param('session_id', '', PARAM_RAW_TRIMMED); // Stripe remplit ce placeholder
$token      = optional_param('t', '', PARAM_ALPHANUMEXT);          // jeton à usage unique

$PAGE->set_url(UrlFactory::payment_success(['pid' => $prid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('payment_success_title', 'local_subscriptions'));

global $DB, $CFG, $SITE;

// Récupère la payment_request
$pr = $DB->get_record('subscription_payment_request', ['id' => $prid], '*', IGNORE_MISSING);

// Si la PR est payée et qu'on a un jeton valide, et que l'utilisateur n'est pas loggé → auto-login sécurisé
if ($pr && in_array($pr->status ?? '', [Status::PAID,Status::COMPLETED], true) && (!isloggedin() || isguestuser())) {
    $tokvalid = !empty($token) && !empty($pr->login_token)
        && hash_equals((string)$pr->login_token, (string)$token)
        && !empty($pr->login_token_expires) && (int)$pr->login_token_expires >= time();

    if ($tokvalid && !empty($pr->email)) {
        // Récupère l'utilisateur créé par le webhook
        $user = $DB->get_record('user', ['email' => core_text::strtolower($pr->email), 'deleted' => 0], '*', IGNORE_MISSING);
        if ($user) {
            require_once($CFG->dirroot.'/lib/classes/session/manager.php');
            require_once($CFG->dirroot.'/user/lib.php');

            // Invalide le jeton (usage unique)
            $pr->login_token = null;
            $pr->login_token_expires = null;
            $pr->last_update = time();
            $DB->update_record('subscription_payment_request', $pr);

            // Connexion
            \core\session\manager::login_user($user);

            // Forcer le changement de mot de passe si pas déjà activé
            if (empty($user->forcepasswordchange)) {
                $user->forcepasswordchange = 1;
                $DB->update_record('user', $user);
            }

            // Redirection : page "changer le mot de passe" puis retour à l'accueil
            $return  = new \moodle_url('/');
            $changepw = new \moodle_url('/login/change_password.php', ['returnurl' => $return->out(false)]);
            redirect($changepw);
        }
    }
}

// Affichage UX
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($SITE->fullname));

if ($pr && in_array($pr->status ?? '', [Status::PAID,Status::COMPLETED], true)) {
    // Paiement validé (webhook passé)
    echo $OUTPUT->notification(get_string('payment_success_thanks', 'local_subscriptions'), \core\output\notification::NOTIFY_SUCCESS);

    // Si l'utilisateur est déjà connecté (compte existant), lien rapide vers tableau de bord
    if (isloggedin() && !isguestuser()) {
        echo html_writer::div(
            html_writer::link(UrlFactory::my_subscriptions(), get_string('view_my_subscriptions', 'local_subscriptions')),
            'my-4'
        );
    } else {
        // Sinon, on indique de vérifier l'email (au cas où le jeton/auto-login n'a pas été possible)
        echo html_writer::div(
            get_string('payment_success_check_email', 'local_subscriptions'),
            'my-3 text-muted'
        );
    }

} else {
    // Pas encore "paid" → soit le webhook n'a pas encore été reçu, soit autre statut
    echo $OUTPUT->notification(get_string('payment_pending_msg', 'local_subscriptions'), \core\output\notification::NOTIFY_INFO);

    echo html_writer::div(
        html_writer::link(UrlFactory::subscribe(), get_string('back_to_plans', 'local_subscriptions')),
        'my-4'
    );

    // Indication utile au support
    if (!empty($session_id)) {
        echo html_writer::div(
            get_string('sessiondisplay', 'local_subscriptions', $session_id),
            'text-muted small'
        );
    }
}

echo $OUTPUT->footer();