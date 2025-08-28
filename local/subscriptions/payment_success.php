<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');

use core\output\notification;
use core\output\notification as Notify;
use local_subscriptions\mailer;

$sessionid = required_param('session_id', PARAM_RAW_TRIMMED);

// Charge Stripe SDK.
require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php');
\Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret'));

// Récupération de la session depuis Stripe (avec paiements).
try {
    $session = \Stripe\Checkout\Session::retrieve([
        'id' => $sessionid,
        'expand' => ['payment_intent', 'customer'],
    ]);
} catch (\Throwable $e) {
    // Option 1: jeter une exception claire
    throw new \moodle_exception(
        'stripe_invalidsession',
        'local_subscriptions',
        '',
        null,
        $e->getMessage()
    );
    // (pense à ajouter la chaîne de langue, voir plus bas)
}

global $DB, $USER;

// Vérifie que l’on a bien une payment_request correspondante.
$paymentreq = $DB->get_record('subscription_payment_request', ['sessionid' => $session->id], '*', IGNORE_MISSING);
if (!$paymentreq) {
    // Fallback : on tente par payment_link si jamais (pas idéal, mais utile si migration).
    $paymentreq = $DB->get_record('subscription_payment_request', ['payment_link' => $session->url], '*', MUST_EXIST);
}

// Idempotence : si déjà traité, on affiche la page de succès.
if (in_array($paymentreq->status, ['paid', 'completed'])) {
    redirect(new moodle_url('/user/profile.php'), get_string('payment_already_processed', 'local_subscriptions'), null, notification::NOTIFY_SUCCESS);
}

// Vérifie le statut Stripe (paiement réussi).
$pi = $session->payment_intent ?? null;
if (is_string($pi)) {
    \Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret'));
    $pi = \Stripe\PaymentIntent::retrieve($pi);
}
if (!$pi || (is_object($pi) && ($pi->status ?? '') !== 'succeeded')) {
    throw new \moodle_exception(
        'stripe_paymentsucceededrequired',
        'local_subscriptions',
        '',
        null,
        'PaymentIntent not in succeeded state'
    );
}

// Commence une transaction DB pour rester atomique.
$transaction = $DB->start_delegated_transaction();

// Met à jour la payment_request.
$paymentreq->status        = 'paid';
$paymentreq->transactionid = $pi->id;
$paymentreq->payment_date  = time();
$paymentreq->response_json = json_encode($session);
$DB->update_record('subscription_payment_request', $paymentreq);

// Récupération/Création de l’utilisateur (invité possible).
require_once($CFG->dirroot . '/user/lib.php');

$email     = $paymentreq->email ?: ($session->customer_details->email ?? null);
$firstname = $paymentreq->firstname ?: ($session->customer_details->name ?? '');
$lastname  = $paymentreq->lastname ?: '';

if (empty($email)) {
    throw new \moodle_exception(
        'stripe_noemail',
        'local_subscriptions',
        '',
        null,
        'Stripe returned no email'
    );
}

$isnewuser = false;
$user = $DB->get_record('user', ['email' => core_text::strtolower($email), 'deleted' => 0], '*', IGNORE_MISSING);
if (!$user) {
    $isnewuser = true;
    $username    = local_subscriptions_generate_unique_username($firstname ?? '', $lastname ?? '', $email ?? '');
    $tmpPassword = random_string(16);

    $u = (object)[
        'auth'               => 'manual',
        'confirmed'          => 1,
        'mnethostid'         => $CFG->mnet_localhost_id,
        'username'           => $username,
        'password'           => hash_internal_user_password($tmpPassword),
        'firstname'          => $firstname ?: 'User',
        'lastname'           => $lastname ?: '',
        'email'              => core_text::strtolower($email),
        'timecreated'        => time(),
        'lang'               => current_language(),
        'forcepasswordchange'=> 1, // ← clé !
    ];
    $userid = user_create_user($u, false, false);
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
}

// Calcule start/end de l’abonnement selon la durée du plan.
$plan = $DB->get_record('subscription_plan', ['id' => $paymentreq->planid, 'is_active' => 1], '*', MUST_EXIST);

// Fonction utilitaire pour ajouter la durée.
$start = time();
$duration = $plan->duration_key ?? '1year';
$end = local_subscriptions_compute_enddate($start, $duration);


// Crée la souscription.
$sub = (object)[
    'userid'           => $user->id,
    'planid'           => $plan->id,
    'payment_provider' => 'stripe',                 // ta colonne existe
    'start_date'       => $start,                   // bigint NOT NULL
    'end_date'         => $end,                     // bigint NOT NULL
    'status'           => 'active',                 // défaut "active" mais on l’indique
    'last_update'      => time(),                     // NULL autorisé, on le renseigne
    'creation_date'    => time(),                     // NOT NULL (ta table a DEFAULT 0)
    'pricepaid'        => (float)$paymentreq->price, // DECIMAL(10,2) chez toi
    'currency'         => $paymentreq->currency,
    'transactionid'    => $paymentreq->transactionid,
];
$subscriptionid = $DB->insert_record('user_subscription', $sub);

require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');

\local_subscriptions\subscription_manager::enrol_user_to_courses(
    $user->id,
    $plan->id,
    $sub->start_date ?? $start ?? time(),
    $sub->end_date   ?? $end ?? 0
);

// Lier payment_request → user_subscription (si colonne existe).
if ($DB->get_manager()->table_exists('subscription_payment_request') && property_exists($paymentreq, 'subscriptionid')) {
    $paymentreq->subscriptionid = $subscriptionid;
    $DB->update_record('subscription_payment_request', $paymentreq);
}

$transaction->allow_commit();

$PAGE->set_url(new moodle_url('/local/subscriptions/payment_success.php', ['session_id' => $sessionid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
global $SITE;
$PAGE->set_title(get_string('payment_success_title', 'local_subscriptions'));
$PAGE->set_heading(format_string($SITE->fullname));

// Emails (une seule fois)
if (empty($paymentreq->emailsent)) {
    if ($isnewuser) {
        mailer::send_welcome($user, (string)$tmpPassword, $plan, $paymentreq);
        mailer::send_receipt($user, $plan, $paymentreq, $sub);
    } else {
        mailer::send_subscription_update($user, $plan, $paymentreq, $sub);
        mailer::send_receipt($user, $plan, $paymentreq, $sub);
    }
    // Marque comme envoyé pour l’idempotence
    $paymentreq->emailsent = 1;
    if (!$DB->get_manager()->field_exists(new xmldb_table('subscription_payment_request'), new xmldb_field('emailsent'))) {
        // si tu n'as pas la colonne, ignore; sinon ajoute-la via upgrade.php
    } else {
        $DB->update_record('subscription_payment_request', $paymentreq);
    }
}

// Auto‑login
complete_user_login($user);

// Redirection : si nouveau, on l’emmène directement sur "changer le mot de passe", puis accueil
if ($isnewuser) {
    $return = new moodle_url('/'); // la home (ou dashboard)
    $changepw = new moodle_url('/login/change_password.php', ['returnurl' => $return->out(false)]);

    \core\notification::add(
        get_string('changepw_hint', 'local_subscriptions'),
        Notify::NOTIFY_INFO
    );


    redirect($changepw);
} else {
    // utilisateur existant : va sur les cours (home) ou profil, comme tu préfères
    redirect(new moodle_url('/'));
}


echo $OUTPUT->header();
echo $OUTPUT->notification(get_string('payment_success_thanks', 'local_subscriptions'), notification::NOTIFY_SUCCESS);
echo html_writer::div(
    html_writer::tag('p', get_string('payment_success_details', 'local_subscriptions')) .
    html_writer::link(new moodle_url('/user/profile.php'), get_string('goto_my_profile', 'local_subscriptions')),
    'my-4'
);
echo $OUTPUT->footer();

/**
 * Calcule la date de fin à partir d’une durée de plan (1month, 1year, 3years, etc.).
 */
function local_subscriptions_compute_enddate(int $start, string $duration): int {
    $dt = new DateTime('@' . $start);
    $dt->setTimezone(new DateTimeZone(core_date::get_user_timezone()));
    switch ($duration) {
        case '1month':  $dt->modify('+1 month'); break;
        case '3months': $dt->modify('+3 months'); break;
        case '6months': $dt->modify('+6 months'); break;
        case '1year':   $dt->modify('+1 year'); break;
        case '2years':  $dt->modify('+2 years'); break;
        case '3years':  $dt->modify('+3 years'); break;
        default:        $dt->modify('+1 month'); // fallback
    }
    return $dt->getTimestamp();
}
