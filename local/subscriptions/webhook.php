<?php
// local/subscriptions/webhook.php
define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/lib.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/mailer.php');

use local_subscriptions\mailer;

$payload       = @file_get_contents('php://input');
$sig_header    = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = get_config('local_subscriptions', 'stripe_webhook_secret');

require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php');
\Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret'));

error_log('[stripe] got webhook: sig='.substr(($sig_header ?? ''), 0, 12).'..., secret='.substr(($endpoint_secret ?? ''), 0, 12).'...');

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    error_log('[stripe] signature OK, type='.$event->type);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('[stripe] signature FAIL: '.$e->getMessage());
    http_response_code(400); exit();
} catch (\UnexpectedValueException $e) {
    error_log('[stripe] payload FAIL: '.$e->getMessage());
    http_response_code(400); exit();
}


global $DB;

// On traite uniquement les événements utiles.
switch ($event->type) {
    case 'checkout.session.completed':

        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;

        $paymentreq = $DB->get_record('subscription_payment_request', ['sessionid' => $session->id], '*', IGNORE_MISSING);
        if (!$paymentreq) { http_response_code(200); exit(); } // Rien à faire ici.

        // Idempotence : si déjà paid, on ne refait rien.
        if (in_array($paymentreq->status, ['paid', 'completed'])) { http_response_code(200); exit(); }

        // Récupère le payment_intent pour vérifier l’état.
        $pi = null;
        if (!empty($session->payment_intent)) {
            $pi = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        }
        if (!$pi || $pi->status !== 'succeeded') { http_response_code(200); exit(); }

        // Finalise la demande + crée souscription (mêmes étapes que payment_success.php).
        // → Option : factoriser ceci dans une fonction utilitaire partagée.

        $transaction = $DB->start_delegated_transaction();
        $paymentreq->status        = 'paid';
        $paymentreq->transactionid = $pi->id;
        $paymentreq->payment_date  = time();
        $paymentreq->response_json = json_encode($session);
        $DB->update_record('subscription_payment_request', $paymentreq);

        // Récup/Création user (cf. payment_success.php)… puis insertion user_subscription…
        // Astuce: tu peux déplacer la logique dans local/subscriptions/classes/payment/finalizer.php
        // et l'appeler ici + dans payment_success.php pour éviter le code dupliqué.

        require_once($CFG->dirroot . '/user/lib.php');

        // 1) Email (depuis la demande ou Stripe)
        $email = $paymentreq->email ?: ($session->customer_details->email ?? null);
        if (!$email) { error_log('[stripe] missing email'); http_response_code(200); exit; }

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

        // 2) Dates selon la durée du plan
        $plan = $DB->get_record('subscription_plan', ['id'=>$paymentreq->planid, 'is_active'=>1], '*', MUST_EXIST);
        $start = time();
        $duration = $plan->duration_key ?? '1year';
        $end = local_subscriptions_compute_enddate($start, $duration);


        // 3) Créer la souscription
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

        $subid = $DB->insert_record('user_subscription', $sub);

        require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');

        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            $user->id,
            $plan->id,
            $sub->start_date ?? $start ?? time(),
            $sub->end_date   ?? $end ?? 0
        );

        // 4) Lier la demande si colonne présente
        if (property_exists($paymentreq, 'subscriptionid')) {
            $paymentreq->subscriptionid = $subid;
            $DB->update_record('subscription_payment_request', $paymentreq);
        }

        error_log("[stripe] user_subscription $subid created for user {$user->id}");

        $transaction->allow_commit();

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

        break;

    case 'checkout.session.expired': {
        $session = $event->data->object;
        error_log('[subs][wh] session.expired id='.$session->id);

        $pr = $DB->get_record('subscription_payment_request', ['sessionid' => $session->id], '*', IGNORE_MISSING);
        if ($pr && $pr->status === 'pending') {
            $pr->status = 'expired';
            $pr->last_attempt = time();
            $DB->update_record('subscription_payment_request', $pr);
            error_log('[subs][wh] PR #'.$pr->id.' -> expired');
            local_subscriptions\mailer::send_abandoned($pr);
        } else {
            error_log('[subs][wh] skip expired: no PR for session or status='.$pr->status ?? 'null');
        }
        http_response_code(200); exit;
    }

    case 'payment_intent.payment_failed': {
        /** @var \Stripe\PaymentIntent $pi */
        $pi = $event->data->object;
        $pidmeta = $pi->metadata->payment_request_id ?? '';
        error_log('[subs][wh] PI failed id='.$pi->id.' meta.prid='.$pidmeta.' status='.$pi->status);

        $pr = null;
        if (!empty($pidmeta)) {
            $pr = $DB->get_record('subscription_payment_request', ['id' => (int)$pidmeta], '*', IGNORE_MISSING);
        }

        // Filet de secours si, un jour, metadata absente :
        if (!$pr) {
            // 1) si tu stockes déjà le PI id dans PR->transactionid (après succès/essai)
            $pr = $DB->get_record('subscription_payment_request', ['transactionid' => $pi->id], '*', IGNORE_MISSING);

            // 2) (optionnel) retrouver la Session via le PI puis mapper par sessionid
            if (!$pr) {
                \Stripe\Stripe::setApiKey(get_config('local_subscriptions','stripe_secret'));
                try {
                    // Stripe autorise la liste des sessions par PI
                    $sessions = \Stripe\Checkout\Session::all(['payment_intent' => $pi->id, 'limit' => 1]);
                    if (!empty($sessions->data)) {
                        $sess = $sessions->data[0];
                        $pr = $DB->get_record('subscription_payment_request', ['sessionid' => $sess->id], '*', IGNORE_MISSING);
                        if ($pr) { error_log('[subs][wh] matched PR via sessionid from PI'); }
                    }
                } catch (\Throwable $e) {
                    error_log('[subs][wh] list sessions by PI failed: '.$e->getMessage());
                }
            }
        }

        if ($pr && $pr->status === 'pending') {
            $pr->status       = 'failed';
            $pr->last_attempt = time();
            $pr->last_error   = json_encode($pi->last_payment_error ?? null);
            $DB->update_record('subscription_payment_request', $pr);
            error_log('[subs][wh] PR #'.$pr->id.' -> failed');
            local_subscriptions\mailer::send_failed($pr);
        } else {
            error_log('[subs][wh] skip fail: no PR or status='.($pr->status ?? 'null'));
        }

        http_response_code(200); exit;
    }

}  

http_response_code(200);
