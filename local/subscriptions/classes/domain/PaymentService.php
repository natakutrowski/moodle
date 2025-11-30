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

        // --- Infos supplémentaires passées via response_json (checkout invité) ---
        $meta = json_decode($pr->response_json ?? '{}', true) ?: [];
        $extra = is_array($meta['extra'] ?? null) ? $meta['extra'] : [];

        // Données issues de checkout.php (téléphone, pays, mot de passe invité)
        $checkoutUser = is_array($meta['checkout_user'] ?? null) ? $meta['checkout_user'] : [];
        $phonecountry = trim((string)($checkoutUser['phonecountry']    ?? $checkoutUser['country'] ?? ''));
        $phonenumber  = trim((string)($checkoutUser['phone']     ?? $checkoutUser['phonenumber']   ?? ''));
        $passwordHash = isset($checkoutUser['password_hash']) ? (string)$checkoutUser['password_hash'] : '';
        if ($passwordHash === '') {
            $passwordHash = null;
        }

        // email prioritaire : PR (saisi côté checkout.php) sinon métadonnées événement
        $email = $pr->email ?? null;
        if (!$email && !empty($e->meta['customer_email'])) { $email = $e->meta['customer_email']; }
        if (!$email) { $transaction->allow_commit(); return; }

        [$user, $isnew, $tmpPassword] = local_subscriptions_ensure_user(
            \core_text::strtolower($email),
            $pr->firstname ?? '',
            $pr->lastname ?? '',
            $passwordHash
        );


        // 5) Calcul des dates à partir du plan
        $plan = $DB->get_record('subscription_plan', ['id' => $pr->planid, 'is_active' => 1], '*', MUST_EXIST);

        // --- Mise à jour du profil utilisateur (téléphone + pays) ---
        // On fait comme pour le Trial : phone -> phone2, country déduit via helper si présent.
        $needsUpdate = false;
        $dialcode = '';

        // Téléphone -> phone2 (ou phone, selon ta convention)
        if ($phonenumber !== '' && empty($user->phone2)) {
            $user->phone2 = $phonenumber;
            $needsUpdate  = true;
        }

        // Pays : on essaye de déduire l’ISO à partir de l’indicatif, sinon on garde la valeur brute.
        if ($phonecountry !== '') {
            $resolvedCountry = null;

            // Normaliser le dial code : on s'attend à quelque chose du type "+33", "+7", etc.
            $dialcode = trim($phonecountry);
            if ($dialcode !== '' && $dialcode[0] !== '+') {
                $dialcode = '+' . $dialcode;   // "33" -> "+33"
            }

            $campuslib = $CFG->dirroot . '/local/campus/lib.php';
            if (is_readable($campuslib)) {
                require_once($campuslib);
                if (function_exists('local_campus_iso_from_phonecode')) {
                    $iso = local_campus_iso_from_phonecode($dialcode); // ex: "+33" -> "FR"
                    if (!empty($iso) && strlen($iso) === 2) {
                        $resolvedCountry = strtoupper($iso);           // "FR", "RU", ...
                    }
                }
            }

            // On ne met à jour que si on a un code ISO valide (2 lettres)
            if (!empty($resolvedCountry) && empty($user->country)) {
                $user->country = $resolvedCountry;
                $needsUpdate   = true;
            }
        }

        // Téléphone -> phone2 (ou phone, selon ta convention)
        if ($phonenumber !== '' && empty($user->phone2) && $dialcode !== '') {
            $user->phone2 = $dialcode.$phonenumber;
            $needsUpdate  = true;
        }


        if ($needsUpdate) {
            user_update_user($user, false);
        }



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

        // Infos utilisateur passées depuis checkout (téléphone, mot de passe invité)
        $checkoutUser = is_array($meta['checkout_user'] ?? null) ? $meta['checkout_user'] : [];
        $phonecountry = $checkoutUser['phonecountry']    ?? null;
        $phonenumber  = $checkoutUser['phonenumber']     ?? null;
        $plainPass    = $checkoutUser['plain_password']  ?? null;


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

                // Fermer toutes les souscriptions d’essai actives pour cet utilisateur
                self::close_active_trials_for_user((int)$sub->userid);

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

                // Fermer toutes les souscriptions d’essai actives pour cet utilisateur
                self::close_active_trials_for_user((int)$sub->userid);
            }
        }

        $transaction->allow_commit();

        // 9) Emails (idempotent + robustes)
        if (empty($pr->emailsent)) {
            try {
                $recipient = \core_user::get_user($user->id, '*', MUST_EXIST); // user COMPLET
                $recipient->mailformat = 1;

                // Est-ce que l'utilisateur avait déjà un abonnement NON trial avant celui-ci ?
                $hadNonTrial = self::user_had_nontrial_subscription_before((int)$user->id, isset($sub->id) ? (int)$sub->id : null);

                // Pour l'email :
                //  - nouvel utilisateur OU
                //  - utilisateur qui n'avait que l'essai jusqu'ici
                // => on envoie WELCOME plutôt que "subscription_updated"
                $isnewForEmail = $isnew || !$hadNonTrial;

                mailer::dispatch(
                    mailer::T_SUBSCRIPTION_EVENT,[
                        'user'          => $recipient,
                        'plan'          => $plan,
                        'pr'            => $pr,
                        'sub'           => $sub,
                        'tmpPassword'   => $tmpPassword,
                        'isupgrade'     => $isupgrade,
                        'isnewuser'     => $isnewForEmail
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
        // Devise
        $cur = !empty($pr->currency) ? strtoupper((string)$pr->currency) : '';

        // 1) Priorité au montant réellement facturé stocké en minor units
        if (property_exists($pr, 'amount_minor') && is_numeric($pr->amount_minor) && (int)$pr->amount_minor > 0) {
            $amt = round(((int)$pr->amount_minor) / 100, 2);
            return [$amt, $cur];
        }

        // 2) Sinon, s'il y a un LOCK, on paie ce qui a été locké (vérrouillé)
        if (property_exists($pr, 'locked_final_price') && is_numeric($pr->locked_final_price) && (float)$pr->locked_final_price > 0) {
            return [round((float)$pr->locked_final_price, 2), $cur];
        }

        // 3) Sinon, tente de lire amount_minor/currency du JSON de réponse (webhook/return)
        if (!empty($pr->response_json)) {
            $meta = json_decode((string)$pr->response_json, true);
            if (is_array($meta)) {
                if (isset($meta['amount_minor']) && is_numeric($meta['amount_minor'])) {
                    $amt = round(((int)$meta['amount_minor']) / 100, 2);
                    if (empty($cur) && !empty($meta['currency'])) { $cur = strtoupper((string)$meta['currency']); }
                    return [$amt, $cur];
                }
                if (isset($meta['currency']) && empty($cur)) {
                    $cur = strtoupper((string)$meta['currency']);
                }
            }
        }

        // 4) Fallback : le prix PR saisi avant checkout
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
        global $DB, $CFG;

        require_once($CFG->dirroot.'/local/subscriptions/classes/trial_manager.php');

        // Sécurité : on n'achète pas un plan d’essai
        if (\local_subscriptions\trial_manager::is_trial_planid($planid)) {
            throw new \moodle_exception('cannot_purchase_trial_plan', 'local_subscriptions');
        }

        // Montant payé (major units) & devise issus du PSP / de la PR
        [$paidAmount, $currency] = self::money_from_pr($pr);
        $currency   = strtoupper(trim($currency ?: 'EUR'));
        $paidAmount = round(max(0.0, (float)$paidAmount), 2);

        // Tolerance et mode strict
        $tolCents = (int)(get_config('local_subscriptions','payments_mismatch_tolerance_cents') ?? 2);
        $tol      = max(0, $tolCents) / 100.0;
        $strict   = !empty(get_config('local_subscriptions','payments_lock_strict'));

        // Par défaut : valeurs tirées du lock PR s'il est présent
        $haslock   = isset($pr->locked_final_price) && (float)$pr->locked_final_price > 0;
        $listPrice = $haslock ? (float)$pr->locked_list_price       : $paidAmount;
        $discPct   = $haslock ? (int)$pr->locked_discount_percent   : 0;
        $discAmt   = $haslock ? (float)$pr->locked_discount_amount  : 0.0;
        $discReas  = $haslock ? ($pr->locked_discount_reason ?? null) : null;
        $expFinal  = $haslock ? (float)$pr->locked_final_price      : $paidAmount;

        // Vérifier l’écart payé vs final attendu
        if (abs($paidAmount - $expFinal) > $tol) {
            if ($strict) {
                throw new \moodle_exception('payment_mismatch_too_large', 'local_subscriptions');
            } else {
                // Ajuster la remise pour que (list - remise = paid)
                if ($listPrice <= 0) { $listPrice = $paidAmount; }
                $discAmt  = round(max(0.0, min($listPrice - $paidAmount, $listPrice)), 2);
                $discPct  = $listPrice > 0 ? (int)round(($discAmt / $listPrice) * 100) : 0;
                $discReas = $discReas ?: 'provider_adjusted';
            }
        }

        // Enregistrement user_subscription
        $sub = (object)[
            'userid'                  => $userid,
            'planid'                  => $planid,
            'payment_provider'        => ($pr->payment_provider ?? ($pr->provider ?? 'unknown')),
            'start_date'              => $start,
            'end_date'                => $end,
            'status'                  => $status,
            'last_update'             => time(),
            'creation_date'           => time(),
            'pricepaid'               => $paidAmount,        // payé réellement
            'currency'                => $currency,
            'transactionid'           => ($pr->transactionid ?? $fallbackTxnId),
            'discount_percent'        => $discPct,           // indicatif
            'discount_amount'         => $discAmt,           // source de vérité
            'discount_reason'         => $discReas,          // 'trial72h' / 'provider_adjusted' / null
        ];

        if (!empty($providerSubId)) { $sub->provider_subscription_id = $providerSubId; }
        if (!empty($providerCusId)) { $sub->provider_customer_id     = $providerCusId; }

        $sub->id = $DB->insert_record('user_subscription', $sub);

        // Inscriptions idempotentes et passage au rôle student
        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            $userid, $planid, $sub->start_date, $sub->end_date
        );
        \local_subscriptions\trial_manager::force_role_student($userid, $planid);

        // Désuspension éventuelle du compte si la souscription est ACTIVE
        require_once($CFG->dirroot.'/user/lib.php'); // user_update_user()

        $user = $DB->get_record('user', ['id' => (int)$userid, 'deleted' => 0], 'id, suspended', IGNORE_MISSING);
        if ($user && !empty($user->suspended)) {
            $user->suspended = 0;
            user_update_user($user, false);
        }

        // Lier la PR à la sub si colonne existante
        if (self::db_field_exists('subscription_payment_request', 'subscriptionid')) {
            $pr->subscriptionid = $sub->id;
            $DB->update_record('subscription_payment_request', $pr);
        }

        return $sub;
    }


    /**
     * Marque comme REPLACED toutes les souscriptions "d’essai" actives pour cet utilisateur.
     * On utilise à la fois :
     *  - la config trial_plan_id (si définie),
     *  - et tous les plans avec is_trial = 1.
     */
    private static function close_active_trials_for_user(int $userid): void {
        global $DB;

        $userid = (int)$userid;
        if ($userid <= 0) {
            return;
        }

        // 1) Récupérer les planids d’essai : config + flags is_trial
        $trialPlanIds = [];

        $trialConfigId = (int)(get_config('local_subscriptions','trial_plan_id') ?? 0);
        if ($trialConfigId > 0) {
            $trialPlanIds[] = $trialConfigId;
        }

        $trialPlans = $DB->get_records('subscription_plan', ['is_trial' => 1], '', 'id');
        foreach ($trialPlans as $p) {
            $trialPlanIds[] = (int)$p->id;
        }

        $trialPlanIds = array_values(array_unique(array_filter($trialPlanIds, fn($v) => $v > 0)));
        if (!$trialPlanIds) {
            return;
        }

        list($inSql, $inParams) = $DB->get_in_or_equal($trialPlanIds, SQL_PARAMS_NAMED, 'pid');

        $now = time();

        // On utilise UNIQUEMENT des paramètres nommés (pas de '?')
        $params = $inParams + [
            'uid'        => $userid,
            'now'        => $now,
            'newstatus'  => Status::REPLACED,
            'lastupdate' => $now,
            'oldstatus'  => Status::ACTIVE,
        ];

        $sql = "
            UPDATE {user_subscription}
            SET status = :newstatus,
                last_update = :lastupdate
            WHERE userid = :uid
            AND planid $inSql
            AND status = :oldstatus
            AND end_date > :now
        ";

        $DB->execute($sql, $params);
    }

    /**
     * Indique si l'utilisateur avait déjà au moins une souscription non-essai
     * (plan is_trial = 0) avant la souscription courante.
     */
    private static function user_had_nontrial_subscription_before(int $userid, ?int $currentSubId = null): bool {
        global $DB;

        $userid = (int)$userid;
        if ($userid <= 0) {
            return false;
        }

        $params = ['u' => $userid];
        $exclSql = '';
        if (!empty($currentSubId)) {
            $exclSql = ' AND s.id <> :curr';
            $params['curr'] = (int)$currentSubId;
        }

        return $DB->record_exists_sql("
            SELECT 1
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.userid = :u
            $exclSql
            AND (p.is_trial IS NULL OR p.is_trial = 0)
        ", $params);
    }


}