<?php
namespace local_subscriptions\domain;

use local_subscriptions\payment\dto\InternalEvent;

/**
 * Gère le premier paiement (Checkout terminé) :
 * - finalise subscription_payment_request
 * - crée/récup l'utilisateur
 * - crée user_subscription
 * - inscrit l'utilisateur aux cours du plan
 * - envoie les emails (idempotent)
 */
class PaymentService {

    /**
     * Événement interne : checkout complété (one-shot ou démarrage d’abonnement).
     * Correspond à Stripe: checkout.session.completed
     */
    public static function on_checkout_completed(InternalEvent $e): void {
        global $DB, $CFG;

        // 1) Retrouver la payment_request (id direct de préférence)
        $pr = null;
        if (!empty($e->payment_request_id)) {
            $pr = $DB->get_record('subscription_payment_request', ['id' => (int)$e->payment_request_id], '*', IGNORE_MISSING);
        }
        // Filet de secours : si on ne l’a pas par ID, essayer par sessionid (ex: Stripe session id)
        if (!$pr && !empty($e->meta['session'])) {
            $pr = $DB->get_record('subscription_payment_request', ['sessionid' => $e->meta['session']], '*', IGNORE_MISSING);
        }
        if (!$pr) { return; }

        // Idempotence : si déjà traité, on sort
        if (in_array($pr->status ?? '', ['paid', 'completed'], true)) { return; }

        // 2) Si provider Stripe et qu'on veut récupérer le PI id, on tente (optionnel)
        $transactionid = null;
        if (($e->meta['provider'] ?? '') === 'stripe' && !empty($e->meta['session'])) {
            // On essaye d'aller chercher le PaymentIntent lié à la session, sans faire planter le flux
            try {
                require_once($CFG->dirroot . '/local/subscriptions/vendor/autoload.php'); // ou /vendor/autoload.php si global
                \Stripe\Stripe::setApiKey(get_config('local_subscriptions', 'stripe_secret_key') ?? get_config('local_subscriptions', 'stripe_secret'));
                $session = \Stripe\Checkout\Session::retrieve($e->meta['session']);
                if (!empty($session->payment_intent)) {
                    $pi = \Stripe\PaymentIntent::retrieve($session->payment_intent);
                    // Si besoin tu peux vérifier $pi->status === 'succeeded'
                    $transactionid = $pi->id;
                }
            } catch (\Throwable $ex) {
                // soft log uniquement
                error_log('[subs][PaymentService] unable to fetch PI: '.$ex->getMessage());
            }
        }

        // 3) Finaliser la demande + réponse json brute utile au debug
        $transaction = $DB->start_delegated_transaction();

        $pr->status         = 'paid';
        if ($transactionid) { $pr->transactionid = $transactionid; }
        $pr->payment_date   = time();
        if (empty($pr->response_json)) {
            // on mémorise un résumé minimal de l’événement
            $pr->response_json = json_encode([
                'event' => 'checkout_completed',
                'provider' => $e->meta['provider'] ?? null,
                'amount_minor' => $e->amount_minor ?? null,
                'currency' => $e->currency ?? null,
            ]);
        }
        
        $pr->last_update = time();
        $DB->update_record('subscription_payment_request', $pr);

        // 4) Récup/Création user
        require_once($CFG->dirroot . '/user/lib.php');

        // email prioritaire : PR (saisi côté checkout.php) sinon métadonnées événement
        $email = $pr->email ?? null;
        if (!$email && !empty($e->meta['customer_email'])) { $email = $e->meta['customer_email']; }
        if (!$email) { $transaction->allow_commit(); return; }

        [$user, $isnew, $tmpPassword] = self::ensure_user(
            \core_text::strtolower($email),
            $pr->firstname ?? '',
            $pr->lastname ?? ''
        );

        // 5) Calcul des dates à partir du plan
        $plan = $DB->get_record('subscription_plan', ['id' => $pr->planid, 'is_active' => 1], '*', MUST_EXIST);
        $start = time();
        $durationkey = $plan->duration_key ?? '1year';
        // Utilise ta fonction utilitaire existante
        $end = function_exists('local_subscriptions_compute_enddate')
            ? local_subscriptions_compute_enddate($start, $durationkey)
            : ($start + 365*24*3600);

        // 6) Créer la souscription

        // Idempotence : si la PR a déjà une sub liée, on ne recrée pas
        if (!empty($pr->subscriptionid)) {
            $exists = $DB->record_exists('user_subscription', ['id' => $pr->subscriptionid]);
            if ($exists) { $transaction->allow_commit(); return; }
        }


        $create_and_link = function(int $uid, int $planid, int $start, int $end, string $status) use ($DB, $pr, $transactionid, $e) {
            $sub = (object)[
                'userid'           => $uid,
                'planid'           => $planid,
                'payment_provider' => $pr->payment_provider ?? ($e->meta['provider'] ?? 'stripe'),
                'start_date'       => $start,
                'end_date'         => $end,
                'status'           => $status,
                'last_update'      => time(),
                'creation_date'    => time(),
                // price/currency : dans ta DB, payment_request a "amount" ou "price"? On essaye les deux.
                'pricepaid'        => isset($pr->price) ? (float)$pr->price : (float)($pr->amount ?? 0),
                'currency'         => $pr->currency ?? '',
                'transactionid'    => $pr->transactionid ?? $transactionid,
            ];

            if (!empty($e->provider_subscription_id)) { $sub->provider_subscription_id = $e->provider_subscription_id; }
            if (!empty($e->provider_customer_id))     { $sub->provider_customer_id     = $e->provider_customer_id;     }
            // start/end: pour un plan récurrent, end = +1 cycle (duration_key du plan)

            $subid = $DB->insert_record('user_subscription', $sub);

            // 7) Enrol dans les cours du plan
            require_once($GLOBALS['CFG']->dirroot . '/local/subscriptions/classes/subscription_manager.php');
            \local_subscriptions\subscription_manager::enrol_user_to_courses(
                $uid,
                $planid,
                $sub->start_date,
                $sub->end_date
            );

            // 8) Lier la demande à la souscription (si colonne présente)
            if (self::db_field_exists('subscription_payment_request', 'subscriptionid')) {
                $pr->subscriptionid = $subid;
                $DB->update_record('subscription_payment_request', $pr);
            }
            return $sub;
        };

        // helper local pour additionner une durée selon duration_key
        $add = function(int $ts, string $duration_key): int {
            $dt = new \DateTime('@'.$ts);
            $dt->setTimezone(new \DateTimeZone(\core_date::get_user_timezone()));
            switch ($duration_key) {
                case '1month':  $dt->modify('+1 month'); break;
                case '3months': $dt->modify('+3 months'); break;
                case '6months': $dt->modify('+6 months'); break;
                case '1year':   $dt->modify('+1 year'); break;
                case '2years':  $dt->modify('+2 years'); break;
                case '3years':  $dt->modify('+3 years'); break;
                default:        $dt->modify('+1 year');  break;
            }
            return $dt->getTimestamp();
        };

        // Lis opération et référence si tu les as sauvées dans PR (sinon, derive-les depuis POST ou l’UI plus tard)
        $operation = $pr->operation ?? '';
        $refsubid  = (int)($pr->reference_subscription_id ?? 0);

        $meta  = json_decode($pr->response_json ?? '{}', true);
        $extra = is_array($meta['extra'] ?? null) ? $meta['extra'] : [];

        if ($operation === '' && $refsubid > 0) { $operation = 'queue_future'; }
        if ($operation === '') { $operation = 'purchase_new'; }

        // Si queue_future, vérifie bien que ref_subid appartient à l’utilisateur et au même plan
        if ($operation === 'queue_future' && $refsubid) {
            $ref = $DB->get_record('user_subscription', ['id'=>$refsubid, 'userid'=>$user->id], '*', IGNORE_MISSING);
            if (!$ref || (int)$ref->planid !== (int)$plan->id) {
                // refuse silencieusement ou bascule en purchase_new
                $operation = 'purchase_new';
                $refsubid  = 0;
            }
        }

        switch ($operation) {

            case 'queue_future': {
                // 1) Base de référence = fin de la souscription passée en ref_subid
                $anchorEnd = time(); // fallback
                if ($refsubid) {
                    $ref = $DB->get_record('user_subscription', ['id'=>$refsubid, 'userid'=>$user->id], '*', IGNORE_MISSING);
                    if ($ref && (int)$ref->planid === (int)$plan->id) {
                        $anchorEnd = max($anchorEnd, (int)$ref->end_date);
                    }
                }

                // 2) Cherche la dernière prolongation déjà enchaînée pour CE user/plan
                //    On prend la date de fin maximum des souscriptions "queued" du même plan
                //    (et, par sécurité, de l'active si son end est > now).
                $maxend = $DB->get_field_sql("
                    SELECT MAX(end_date)
                    FROM {user_subscription}
                    WHERE userid = :u
                    AND planid = :p
                    AND status IN ('queued', 'active')
                ", ['u'=>$user->id, 'p'=>$plan->id]);

                if (!empty($maxend)) {
                    $anchorEnd = max($anchorEnd, (int)$maxend);
                }

                // 3) Le nouveau START est le lendemain (ou la seconde suivante) de l'anchor
                //    et ne doit jamais être dans le passé.
                $start = max($anchorEnd + 1, time());
                // 4) END = START + durée du plan
                $end   = $add($start, $plan->duration_key);

                // 5) Idempotence "chaînage" : si on a déjà créé une queued pour CE start, on sort
                $existsQueued = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $start,
                    'status'     => 'queued',
                ]);

                error_log('[subs][svc][checkout_completed] existsQueued='.(string)$existsQueued.', start='.(string)$start.', end='.(string)$end);



                if ($existsQueued) { $transaction->allow_commit(); return; }    
                $sub = $create_and_link($user->id, $plan->id, $start, $end, ($start > time() ? 'queued' : 'active'));
                break;
            }

            case 'upgrade_prorata': {
                // Clôture l’ancienne sub (même scope) et crée une nouvelle 3 ans à partir de now
                if ($refsubid) {
                    $old = $DB->get_record('user_subscription', ['id'=>$refsubid, 'userid'=>$user->id], '*', IGNORE_MISSING);
                    if ($old) {
                        $old->end_date   = time();
                        $old->status     = 'replaced';
                        $old->last_update= time();
                        $DB->update_record('user_subscription', $old);
                    }
                }
                $start = time();
                $end   = $add($start, $plan->duration_key);
                $sub = $create_and_link($user->id, $plan->id, $start, $end, 'active');
                break;
            }

            case 'upgrade_now_replace_chain': {
                // 0) Scope du plan cible
                $scopeid = (int)$DB->get_field('subscription_plan', 'accessscopeid', ['id' => $plan->id], MUST_EXIST);

                // 1) Retrouver l'ancienne ACTIVE par SCOPE (pas par plan)
                $old = $DB->get_record_sql(
                    "SELECT s.*
                    FROM {user_subscription} s
                    JOIN {subscription_plan} p ON p.id = s.planid
                    WHERE s.userid = :u
                        AND p.accessscopeid = :scope
                        AND s.status = 'active'
                ORDER BY s.start_date DESC
                    LIMIT 1",
                    ['u' => $user->id, 'scope' => $scopeid],
                    IGNORE_MISSING
                );

                // Base de rétrodatation = début de l'ancienne active, sinon fallback now
                $baseStart = $old ? (int)$old->start_date : time();

                // 2) Remplacer toutes les queued du SCOPE (file) → status=replaced
                $queued = $DB->get_records_sql(
                    "SELECT s.*
                    FROM {user_subscription} s
                    JOIN {subscription_plan} p ON p.id = s.planid
                    WHERE s.userid = :u
                        AND p.accessscopeid = :scope
                        AND s.status = 'queued'
                ORDER BY s.end_date ASC",
                    ['u' => $user->id, 'scope' => $scopeid]
                );
                foreach ($queued as $q) {
                    $q->status = 'replaced';
                    $q->last_update = time();
                    $DB->update_record('user_subscription', $q);
                }

                // 3) Mettre l'ancienne ACTIVE en replaced (si trouvée)
                if ($old) {
                    $old->end_date   = time();      // stop maintenant
                    $old->status     = 'replaced';
                    $old->last_update= time();
                    $DB->update_record('user_subscription', $old);
                }

                // 4) Créer la nouvelle sub 3 ANS rétrodatée au début de l'ancienne
                $start = $baseStart;
                $end   = $add($start, $plan->duration_key); // ex. '3years'

                // Idempotence : si une active au même start existe déjà, on sort proprement
                $existsSameStart = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $start,
                    'status'     => 'active',
                ]);
                if ($existsSameStart) { $transaction->allow_commit(); return; }

                // 5) Créer + enrol + lier PR
                $sub = $create_and_link($user->id, $plan->id, $start, $end, 'active');

                $transaction->allow_commit();
                break;
            }



            default: { // 'purchase_new'

                // a) si la PR a un transactionid déjà présent sur une sub → idempotence par transaction
                if (!empty($pr->transactionid) &&
                    $DB->record_exists('user_subscription', ['transactionid' => $pr->transactionid])) {
                    $transaction->allow_commit(); return;
                }

                // b) pour 'purchase_new', on crée avec start = time()
                $startImmediate = time();

                // s’il existe DÉJÀ une sub active créée “à l’instant T” (même user/plan/start exact) → on sort
                $existsSameStart = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $startImmediate,
                    'status'     => 'active',
                ]);
                if ($existsSameStart) { $transaction->allow_commit(); return; }

                $start = time();
                $end   = $add($start, $plan->duration_key);
                $sub = $create_and_link($user->id, $plan->id, $start, $end, 'active');
            }
        }

        $transaction->allow_commit();

        // 9) Emails (une seule fois pour l’idempotence)
        if (empty($pr->emailsent) && class_exists('\local_subscriptions\mailer')) {
            $recipient = clone $user; // sécu HTML
            $recipient->mailformat = 1;

            // envoi
            if ($isnew) {
                \local_subscriptions\mailer::send_welcome($recipient, (string)$tmpPassword, $plan, $pr);
            } else {
                \local_subscriptions\mailer::send_subscription_update($recipient, $plan, $pr, $sub);
            }

            if (!empty($e->provider_subscription_id)) {
                \local_subscriptions\mailer::send_recurring_started($recipient, $plan, $pr);
            }

            \local_subscriptions\mailer::send_receipt($recipient, $plan, $pr, $sub);

            // marquer envoyé si la colonne existe
            if (self::db_field_exists('subscription_payment_request', 'emailsent')) {
                $pr->emailsent = 1;
                $DB->update_record('subscription_payment_request', $pr);
            }
        }
    }

    public static function on_checkout_expired(InternalEvent $e): void {
        global $DB;
        // retrouver la PR via provider_session_id (on l’a enregistré dans create_session.php)
        $pr = null;
        if (!empty($e->meta['session'])) {
            $pr = $DB->get_record('subscription_payment_request',
                ['provider_session_id' => $e->meta['session']], '*', IGNORE_MISSING);
        }
        if (!$pr || ($pr->status ?? '') !== 'pending') { return; }

        $pr->status       = 'expired';
        $pr->last_attempt = time();
        $DB->update_record('subscription_payment_request', $pr);

        // ton mailer existant :
        if (class_exists('\local_subscriptions\mailer')) {
            \local_subscriptions\mailer::send_abandoned($pr);
        }
    }
    public static function on_payment_failed(InternalEvent $e): void {
        global $DB, $CFG;

        // 1) retrouver la PR
        $pr = null;
        if (!empty($e->payment_request_id)) {
            $pr = $DB->get_record('subscription_payment_request', ['id' => (int)$e->payment_request_id], '*', IGNORE_MISSING);
        }
        // filet de secours si metadata absente: remonter via PI -> session -> sessionid
        $piid = $e->meta['payment_intent'] ?? null;
        if (!$pr && $piid) {
            try {
                require_once($CFG->dirroot.'/local/subscriptions/vendor/autoload.php');
                \Stripe\Stripe::setApiKey(get_config('local_subscriptions','stripe_secret_key') ?? get_config('local_subscriptions','stripe_secret'));
                $sessions = \Stripe\Checkout\Session::all(['payment_intent' => $piid, 'limit' => 1]);
                if (!empty($sessions->data)) {
                    $sess = $sessions->data[0];
                    $pr = $DB->get_record('subscription_payment_request', ['sessionid' => $sess->id], '*', IGNORE_MISSING);
                }
            } catch (\Throwable $ex) {
                error_log('[subs][on_payment_failed] PI fallback failed: '.$ex->getMessage());
            }
        }
        if (!$pr || ($pr->status ?? '') !== 'pending') { return; }

        // 2) Récupérer le détail de l'erreur depuis le PaymentIntent (si possible)
        $lastError = null;
        if ($piid) {
            try {
                $pi = \Stripe\PaymentIntent::retrieve($piid);
                $lastError = $pi->last_payment_error ?? null;
            } catch (\Throwable $ex) {
                error_log('[subs][on_payment_failed] fetch PI last_error failed: '.$ex->getMessage());
            }
        }


        // 3) Marquer failed + stocker l'erreur (idempotent: on ne repasse failed que si pending)
        if (($pr->status ?? '') === 'pending') {
            $pr->status       = 'failed';
            $pr->last_attempt = time();
            if ($lastError) {
                $pr->last_error = json_encode($lastError);
            }
            $DB->update_record('subscription_payment_request', $pr);

            if (class_exists('\local_subscriptions\mailer')) {
                \local_subscriptions\mailer::send_failed($pr);
            }
        }
    }

    /**
     * Crée ou récupère un utilisateur à partir de l'email.
     * Retourne [\stdClass $user, bool $isnew, ?string $tmpPassword]
     */
    private static function ensure_user(string $email, string $firstname = '', string $lastname = ''): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['email' => \core_text::strtolower($email), 'deleted' => 0], '*', IGNORE_MISSING);
        if ($user) {
            return [$user, false, null];
        }

        // Génère username unique (reprend ta fonction utilitaire existante)
        if (!function_exists('local_subscriptions_generate_unique_username')) {
            // fallback très simple si jamais la fonction n'existe pas
            $base = \core_text::substr(preg_replace('~[^a-z0-9]+~i', '', $firstname.$lastname), 0, 20);
            if ($base === '') { $base = 'user'; }
            $username = self::unique_username_from_base($base);
        } else {
            $username = local_subscriptions_generate_unique_username($firstname ?? '', $lastname ?? '', $email ?? '');
        }

        $tmpPassword = random_string(16);

        $u = (object)[
            'auth'               => 'manual',
            'confirmed'          => 1,
            'mnethostid'         => $CFG->mnet_localhost_id,
            'username'           => $username,
            'password'           => hash_internal_user_password($tmpPassword),
            'firstname'          => $firstname ?: 'User',
            'lastname'           => $lastname ?: '',
            'email'              => \core_text::strtolower($email),
            'timecreated'        => time(),
            'lang'               => current_language(),
            'forcepasswordchange'=> 1,
        ];
        $userid = user_create_user($u, false, false);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        return [$user, true, $tmpPassword];
    }

    /** Génère un username unique si la fonction utilitaire n'est pas dispo. */
    private static function unique_username_from_base(string $base): string {
        global $DB;
        $candidate = \core_text::strtolower($base);
        $i = 0;
        while (true) {
            $u = $DB->get_record('user', ['username' => $candidate], 'id', IGNORE_MISSING);
            if (!$u) { return $candidate; }
            $i++;
            $candidate = $base . $i;
        }
    }

    /** Teste l'existence d'un champ dans une table (pour emailsent, subscriptionid, etc.). */
    public static function db_field_exists(string $tablename, string $fieldname): bool {
        global $DB;
        $dbman = $DB->get_manager();
        $table = new \xmldb_table($tablename);
        $field = new \xmldb_field($fieldname);
        return $dbman->field_exists($table, $field);
    }

    private static function create_user_subscription(int $userid, int $planid, int $start, int $end, string $status): int {
        global $DB;
        $sub = (object)[
            'userid'        => $userid,
            'planid'        => $planid,
            'start_date'    => $start,
            'end_date'      => $end,
            'status'        => $status,
            'creation_date' => time(),
            'last_update'   => time(),
        ];
        return $DB->insert_record('user_subscription', $sub);
    }


}
