<?php
namespace local_subscriptions\domain;

use local_subscriptions\log\EventLogger;
use local_subscriptions\payment\dto\InternalEvent;
use local_subscriptions\constants\Operation;
use local_subscriptions\constants\Status;
use local_subscriptions\payment\TransactionIdResolver;
use local_subscriptions\support\Duration;
use local_subscriptions\payment\Provider;
use local_subscriptions\mailer;

require_once(__DIR__ . '/../../lib/user_subs_lib.php');

/**
 * Gère le premier paiement (Checkout terminé) :
 * - finalise subscription_payment_request
 * - crée/récup l'utilisateur
 * - crée user_subscription
 * - inscrit l'utilisateur aux cours du plan
 * - envoie les emails (idempotent)
 */
class PaymentService {


    private static function secretStripeKey(): string {

        $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
        $env = ($env === 'live') ? 'live' : 'test';

        return get_config('local_subscriptions', "stripe_{$env}_secret") ?: '';
    }

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
        if (in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true)) { return; }

        // 3) Transaction ID via le resolver (agnostique Stripe/Alfa/…)
        $transactionid = TransactionIdResolver::resolve_from_spr($pr, $e->meta ?? []);

        // 4) Finaliser la demande + réponse json brute utile au debug
        $transaction = $DB->start_delegated_transaction();

        $pr->status         = Status::PAID;
        if (!empty($transactionid) && empty($pr->transactionid)) { $pr->transactionid = (string)$transactionid; }
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

        [$user, $isnew, $tmpPassword] = local_subscriptions_ensure_user(
            \core_text::strtolower($email),
            $pr->firstname ?? '',
            $pr->lastname ?? ''
        );

        // 5) Calcul des dates à partir du plan
        $plan = $DB->get_record('subscription_plan', ['id' => $pr->planid, 'is_active' => 1], '*', MUST_EXIST);

        // 6) Créer la souscription

        // Idempotence : si la PR a déjà une sub liée, on ne recrée pas
        if (!empty($pr->subscriptionid)) {
            $exists = $DB->record_exists('user_subscription', ['id' => $pr->subscriptionid]);
            if ($exists) { $transaction->allow_commit(); return; }
        }

        // Lis opération et référence si tu les as sauvées dans PR (sinon, derive-les depuis POST ou l’UI plus tard)
        $operation = $pr->operation ?? '';
        $refsubid  = (int)($pr->reference_subscription_id ?? 0);

        $meta  = json_decode($pr->response_json ?? '{}', true);
        $extra = is_array($meta['extra'] ?? null) ? $meta['extra'] : [];

        if ($operation === '' && $refsubid > 0) { $operation = Operation::QUEUE_FUTURE; }
        if ($operation === '') { $operation = Operation::PURCHASE_NEW; }

        // Si queue_future et ref_subid fourni, vérifier simplement qu'il appartient à l'utilisateur.
        // On ancre de toute façon AU NIVEAU DU SCOPE (pas « même plan »).
        if ($operation === Operation::QUEUE_FUTURE && $refsubid) {
            $ref = $DB->get_record('user_subscription', ['id'=>$refsubid, 'userid'=>$user->id], '*', IGNORE_MISSING);
            if (!$ref) {
                $refsubid = 0; // on continuera avec l’ancre scope
            }
        }

        $isupgrade = false;
        $sub = null; // la sub créée dans le case (pour les emails/receipt)

        switch ($operation) {

            case Operation::QUEUE_FUTURE: {

                // 1) Base de référence : scope du plan cible
                $targetscopeid = (int)$DB->get_field('subscription_plan', 'accessscopeid', ['id' => $plan->id], MUST_EXIST);

                // 2) ANCRE = MAX(end_date) de toute la chaîne (active + queued) du MÊME SCOPE
                $anchorEnd = (int)time(); // fallback
                $maxendByScope = $DB->get_field_sql("
                    SELECT MAX(s.end_date)
                    FROM {user_subscription} s
                    JOIN {subscription_plan} p ON p.id = s.planid
                    WHERE s.userid = :u
                    AND p.accessscopeid = :scope
                    AND s.status IN ('".Status::ACTIVE."','".Status::QUEUED."')
                ", ['u'=>$user->id, 'scope'=>$targetscopeid]);

                if (!empty($maxendByScope)) {
                    $anchorEnd = max($anchorEnd, (int)$maxendByScope);
                }

                // 3) Démarrage = seconde suivante après l’ancre (et jamais dans le passé)
                $start = max($anchorEnd + 1, time());
                // 4) Fin = START + durée du plan
                $end   = Duration::add_duration_utc($start, $plan->duration_key);

                // 5) Idempotence “chaînage”
                $existsQueued = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $start,
                    'status'     => Status::QUEUED,
                ]);
                if ($existsQueued) { $transaction->allow_commit(); return; }

                // 6) Créer la brique (évidemment queued si start > now)
                $sub = self::create_and_link_sub(
                    $user->id,
                    $plan->id,
                    $start,
                    $end,
                    ($start > time() ? Status::QUEUED : Status::ACTIVE),
                    $pr,
                    $e->provider_subscription_id ?? null,
                    $e->provider_customer_id ?? null,
                    $transactionid ?? null
                );

                break;

            }

            case Operation::UPGRADE_NOW_REPLACE_CHAIN: {
                // ---- 0) LIRE LES META DE LA PR (fenêtre & liste) ----
                $meta  = json_decode($pr->response_json ?? '{}', true) ?: [];
                $extra = $meta['extra'] ?? $meta; // suivant comment tu as stocké
                $win   = $extra['upgrade_window'] ?? null; // ['start'=>ts,'end'=>ts]
                $ids   = $extra['replace_ids']    ?? [];

                // ---- 1) FENÊTRE CIBLE [newstart ; newend) ----
                $scopeid = (int)$DB->get_field('subscription_plan', 'accessscopeid', ['id' => $plan->id], MUST_EXIST);

                // ancienne ACTIVE (fallback pour newstart si win absent)
                $old = $DB->get_record_sql("
                    SELECT s.*
                    FROM {user_subscription} s
                    JOIN {subscription_plan} p ON p.id = s.planid
                    WHERE s.userid = :u
                    AND p.accessscopeid = :scope
                    AND s.status = '".Status::ACTIVE."'
                    ORDER BY s.start_date DESC
                    LIMIT 1
                ", ['u' => $user->id, 'scope' => $scopeid], IGNORE_MISSING);

                $baseStart = $old ? (int)$old->start_date : (int)time();

                $durSec   = \local_subscriptions\domain\SubscriptionAdvisor::duration_to_seconds($plan->duration_key ?? '1year');
                $newstart = isset($win['start']) ? (int)$win['start'] : $baseStart;
                $newend   = isset($win['end'])   ? (int)$win['end']   : ($newstart + $durSec);

                // ---- 2) CRÉER LA NOUVELLE SUB (active) RÉTRODATÉE SUR LA FENÊTRE ----
                // (idempotence si une active existe déjà au même start)
                $existsSameStart = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $newstart,
                    'status'     => Status::ACTIVE,
                ]);
                if ($existsSameStart) { $transaction->allow_commit(); return; }

                // CRÉER la nouvelle sub (active, rétrodatée) via TA FONCTION : pricepaid/currency/lien PR OK !
                $sub = self::create_and_link_sub(
                    $user->id,
                    $plan->id,
                    $newstart,
                    $newend,
                    Status::ACTIVE,
                    $pr,
                    $e->provider_subscription_id ?? null,
                    $e->provider_customer_id ?? null,
                    $transactionid ?? null
                );

                // ---- 3) QUELS ÉLÉMENTS REMPLACER ? ----

                // Liste à remplacer (ACTIVE + QUEUED chevauchant la fenêtre) : soit PR -> replace_ids, soit on recalcule
                $toReplace = [];

                if (!empty($ids)) {
                    // a) L’Advisor a fourni une liste -> on la charge
                    $ids = array_filter(array_map('intval', (array)$ids), fn($x) => $x > 0);
                    if (!empty($ids)) {
                        list($inSql, $inParams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'rid');
                        $toReplace = $DB->get_records_select('user_subscription', "id $inSql", $inParams);
                    }
                }

                if (empty($toReplace)) {
                    // b) Filet : recalcule sur le MÊME scope
                    $toReplace = \local_subscriptions\domain\SubscriptionAdvisor::list_scope_overlaps(
                        (int)$user->id, (int)$scopeid, (int)$newstart, (int)$newend, [Status::ACTIVE,Status::QUEUED]
                    );
                }

                // >>> NORMALISATION robuste (id obligatoire)
                $norm = [];
                foreach ($toReplace as $row) {
                    if (!is_object($row) || !isset($row->id)) { continue; }
                    $norm[(int)$row->id] = $row;
                }
                $toReplace = $norm;

                // Inclure la vieille ACTIVE si elle manque (ex. 1 mois actif)
                if ($old && isset($old->id) && !isset($toReplace[(int)$old->id])) {
                    $toReplace[(int)$old->id] = $old;
                }

                // Remplacer toute la pile (sauf la sub qu’on vient de créer)
                foreach ($toReplace as $row) {
                    if (!isset($row->id)) { continue; }              // <<< évite Undefined property:id
                    if ((int)$row->id === (int)$sub->id) { continue; }
                    $row->status      = Status::REPLACED;
                    $row->last_update = time();
                    $DB->update_record('user_subscription', $row);
                }

                // (Ré)inscrire idempotent
                \local_subscriptions\subscription_manager::enrol_user_to_courses(
                    (int)$sub->userid, (int)$sub->planid, (int)$sub->start_date, (int)$sub->end_date
                );

                // Flag pour emails
                $isupgrade = true;  

                // ---- 6) LOG (facultatif) ----
                EventLogger::log((int)$sub->id, Operation::UPGRADE_NOW_REPLACE_CHAIN, $pr->id ?? null, [
                    'window'   => ['start'=>$newstart,'end'=>$newend],
                    'replaced' => array_map(fn($x)=> (int)$x->id, $toReplace),
                ]);

                break;
            }

            default: { // Operation::PURCHASE_NEW

                // a) si la PR a un transactionid déjà présent sur une sub → idempotence par transaction
                if (!empty($pr->transactionid) &&
                    $DB->record_exists('user_subscription', ['transactionid' => $pr->transactionid])) {
                    $transaction->allow_commit(); return;
                }

                // b) pour 'purchase_new', on crée avec start = time()
                $start = time();

                // s’il existe DÉJÀ une sub active créée “à l’instant T” (même user/plan/start exact) → on sort
                $existsSameStart = $DB->record_exists('user_subscription', [
                    'userid'     => $user->id,
                    'planid'     => $plan->id,
                    'start_date' => $start,
                    'status'     => Status::ACTIVE,
                ]);
                if ($existsSameStart) { $transaction->allow_commit(); return; }

                $end = Duration::add_duration_utc($start, $plan->duration_key);
                $sub = self::create_and_link_sub(
                    $user->id,
                    $plan->id,
                    $start,
                    $end,
                    Status::ACTIVE,
                    $pr,
                    $e->provider_subscription_id ?? null,
                    $e->provider_customer_id ?? null,
                    $transactionid ?? null
                );
            }
        }

        $transaction->allow_commit();

        // 9) Emails (idempotent + robustes)
        if (empty($pr->emailsent)) {
            try {
                $recipient = \core_user::get_user($user->id, '*', MUST_EXIST); // user COMPLET
                $recipient->mailformat = 1;

                mailer::dispatch(
                    mailer::T_SUBSCRIPTION_EVENT,[
                        'user'          => $recipient,
                        'plan'          => $plan,
                        'pr'            => $pr,
                        'sub'           => $sub,
                        'tmpPassword'   => $tmpPassword,
                        'isupgrade'     => $isupgrade,
                        'isnewuser'     => $isnew
                    ]
                );

                if (!empty($e->provider_subscription_id)) {
                    mailer::dispatch(
                        mailer::T_RECURRING_STARTED,[
                            'user'  => $recipient,
                            'plan'  => $plan,
                            'pr'    => $pr
                        ]
                    );
                }

                mailer::dispatch(
                    mailer::T_RECEIPT,[
                        'user'   => $recipient,
                        'plan'   => $plan,
                        'pr'     => $pr,
                        'sub'    => $sub
                    ]
                );

                $pr->emailsent = 1;
                $DB->update_record('subscription_payment_request', $pr);

            } catch (\Throwable $ex) {
                error_log('[subs][mail] '.$ex->getMessage());
                // on continue sans casser le flux
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
        if (!$pr || ($pr->status ?? '') !== Status::PENDING) { return; }

        $pr->status       = Status::EXPIRED;
        $pr->last_attempt = time();
        $DB->update_record('subscription_payment_request', $pr);

        mailer::dispatch(
            mailer::T_PAYMENT_ABANDONED,[
                'pr' => $pr
            ]
        );
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
                \Stripe\Stripe::setApiKey(self::secretStripeKey());
                $sessions = \Stripe\Checkout\Session::all(['payment_intent' => $piid, 'limit' => 1]);
                if (!empty($sessions->data)) {
                    $sess = $sessions->data[0];
                    $pr = $DB->get_record('subscription_payment_request', ['sessionid' => $sess->id], '*', IGNORE_MISSING);
                }
            } catch (\Throwable $ex) {
                error_log('[subs][on_payment_failed] PI fallback failed: '.$ex->getMessage());
            }
        }
        if (!$pr || ($pr->status ?? '') !== Status::PENDING) { return; }

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
        if (($pr->status ?? '') === Status::PENDING) {
            $pr->status       = Status::FAILED;
            $pr->last_attempt = time();
            if ($lastError) {
                $pr->last_error = json_encode($lastError);
            }
            $DB->update_record('subscription_payment_request', $pr);

            mailer::dispatch(
                mailer::T_PAYMENT_FAILED,[
                    'pr' => $pr
                ]
            );
        }
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

    /**
     * Extrait (amount_major, currency) depuis la PR.
     * Convention: PR.price est DÉJÀ en unités "major" (EUR), 2 décimales.
     */
    private static function money_from_pr(\stdClass $pr): array {
        $cur = !empty($pr->currency) ? strtoupper((string)$pr->currency) : '';
        $amt = (isset($pr->price) && is_numeric($pr->price)) ? round((float)$pr->price, 2) : 0.0;
        return [$amt, $cur];
    }

    /** Crée une souscription + enrole + lie PR — équivalent de ta closure $create_and_link, mais robuste et testable. */
    private static function create_and_link_sub(
        int $userid,
        int $planid,
        int $start,
        int $end,
        string $status,
        \stdClass $pr,
        ?string $providerSubId,
        ?string $providerCusId,
        ?string $fallbackTxnId
    ): \stdClass {
        global $DB;

        [$amountMajor, $currency] = self::money_from_pr($pr);

        $sub = (object)[
            'userid'           => $userid,
            'planid'           => $planid,
            'payment_provider' => $pr->payment_provider ?? ($pr->provider ?? Provider::STRIPE),
            'start_date'       => $start,
            'end_date'         => $end,
            'status'           => $status,
            'last_update'      => time(),
            'creation_date'    => time(),
            'pricepaid'        => $amountMajor,
            'currency'         => $currency,
            'transactionid'    => $pr->transactionid ?? $fallbackTxnId,
        ];
        if (!empty($providerSubId)) { $sub->provider_subscription_id = $providerSubId; }
        if (!empty($providerCusId)) { $sub->provider_customer_id     = $providerCusId; }

        $sub->id = $DB->insert_record('user_subscription', $sub);

        // Enrol (idempotent) — garde ton manager, mais évite require_once dans le hot path
        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            $userid, $planid, $sub->start_date, $sub->end_date
        );

        // Lier la PR à la sub (si colonne existante)
        if (self::db_field_exists('subscription_payment_request', 'subscriptionid')) {
            $pr->subscriptionid = $sub->id;
            $DB->update_record('subscription_payment_request', $pr);
        }

        return $sub;
    }
}