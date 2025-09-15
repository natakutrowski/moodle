<?php
namespace local_subscriptions\domain;

use local_subscriptions\payment\dto\InternalEvent;

class SubscriptionService {

    /** Paiement récurrent réussi (invoice) → prolonger la souscription d'un cycle */
    public static function on_invoice_paid(InternalEvent $e): void {
        global $DB;

        // Ne pas prolonger au tout premier paiement (Stripe crée déjà la 1ère période)
        if (!empty($e->meta['billing_reason']) && $e->meta['billing_reason'] === 'subscription_create') {
            error_log('[subs][svc][invoice_paid] skip initial invoice for sub '.$e->provider_subscription_id);
            return;
        }
     
        $subid = $e->provider_subscription_id ?? null;
        if (!$subid) return;

        // retrouve la user_subscription liée
        $sub = $DB->get_record('user_subscription', ['provider_subscription_id' => $subid], '*', IGNORE_MISSING);
        if (!$sub) { error_log('[subs][svc][invoice_paid] no local sub for '.$subid); return; }

        $invoiceid = $e->meta['invoice'] ?? null;
        if ($invoiceid && $sub && !empty($sub->last_invoice_id) && $sub->last_invoice_id === $invoiceid) {
            // déjà traité — on ne prolonge pas deux fois
            return;
        }

        // retrouve le plan pour connaître la durée du cycle
        $plan = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', IGNORE_MISSING);
        if (!$plan) return;

        $add = function(int $ts, string $duration_key): int {
            $dt = new \DateTime('@'.$ts); $dt->setTimezone(new \DateTimeZone('UTC'));
            switch ($duration_key) {
                case '1month':  $dt->modify('+1 month'); break;
                case '3months': $dt->modify('+3 months'); break;
                case '6months': $dt->modify('+6 months'); break;
                case '1year':   $dt->modify('+1 year'); break;
                case '2years':  $dt->modify('+2 years'); break;
                case '3years':  $dt->modify('+3 years'); break;
                default:        $dt->modify('+1 month');  break; // fallback
            }
            return $dt->getTimestamp();
        };

        // prolonge à partir de end_date s'il est futur, sinon à partir de maintenant
        $oldend = (int)$sub->end_date;

        // Préférence : caler sur Stripe (period end de l'invoice)
        $periodend = (int)($e->meta['current_period_end'] ?? 0);

        if ($periodend > 0) {
            // On avance au plus loin des deux (idempotent, pas de double prolongation)
            $sub->end_date = max((int)$sub->end_date, $periodend);
        } else {
            // Fallback si jamais l'event n'a pas la période (rare)
            $base = ($sub->end_date && $sub->end_date > time()) ? (int)$sub->end_date : time();
            $sub->end_date = $add($base, $plan->duration_key);
        }

        error_log("[paid] oldend={$oldend} periodend={$periodend} newend={$sub->end_date}");

        $sub->payment_failed = 0;
        $sub->last_update = time();
        $DB->update_record('user_subscription', $sub);

        if ($invoiceid) {
            $sub->last_invoice_id = $invoiceid;
            $sub->last_update = time();
            $DB->update_record('user_subscription', $sub);
        }

        // --- LOG: facture payée (renouvellement OK) ---
        self::log_subscription_event(
            (int)$sub->id,
            'invoice_paid',
            $e->meta['invoice'] ?? null,
            [
                'provider'           => 'stripe',
                'provider_sub'       => $e->provider_subscription_id ?? null,
                'billing_reason'     => $e->meta['billing_reason'] ?? null,
                'current_period_end' => $e->meta['current_period_end'] ?? null,
                'amount_minor'       => $e->amount_minor ?? null,
                'currency'           => $e->currency ?? null,
            ]
        );

        // Envoi du mail de confirmation renouvellement
        try {
            if (class_exists('\local_subscriptions\mailer')) {
                // Un user COMPLET (tous les champs attendus par fullname())
                $user    = \core_user::get_user($sub->userid, '*', MUST_EXIST);
                $planrec = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', MUST_EXIST);

                $amount   = isset($e->amount_minor) ? ((float)$e->amount_minor / 100.0) : null;
                $currency = isset($e->currency) ? strtoupper($e->currency) : null;
                $invoice  = $e->meta['invoice'] ?? null;

                if (method_exists('\local_subscriptions\mailer', 'send_renewal_ok')) {
                    \local_subscriptions\mailer::send_renewal_ok($user, $planrec, $sub, $amount, $currency, $invoice, $oldend);
                } else {
                    error_log('[subs][svc][invoice_paid] no mailer method for renewal');
                }
            }
        } catch (\Throwable $ex) {
            error_log('[subs][mail][renewal] '.$ex->getMessage());
            // on poursuit le flux pour étendre l’accès aux cours quoi qu’il arrive
        }

        // (après avoir mis à jour end_date)
        global $CFG;
        require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');

        // Si l'utilisateur avait été suspendu entre-temps, on réactive proprement (idempotent)
        \local_subscriptions\subscription_manager::enrol_user_to_courses(
            (int)$sub->userid,
            (int)$sub->planid,
            (int)($sub->start_date ?: time()),
            (int)($sub->end_date ?: 0)
        );

    }

    /** Échec de prélèvement récurrent */  
    public static function on_invoice_failed(InternalEvent $e): void {
        global $DB, $CFG;

        // --- 0) Récup des infos de base
        $subid     = $e->provider_subscription_id ?? null;     // ex: sub_...
        $invoiceid = $e->meta['invoice'] ?? null;              // ex: in_...
        $amount    = isset($e->amount_minor) ? ((float)$e->amount_minor / 100.0) : null;
        $currency  = isset($e->currency) ? strtoupper($e->currency) : null;
        $failcode  = $e->meta['last_payment_error'] ?? $e->meta['failure_reason'] ?? null;
        $nexttry   = isset($e->meta['next_payment_attempt']) ? (int)$e->meta['next_payment_attempt'] : null;

        error_log('[subs][failed] enter subid=' . ($subid ?: '∅') . ' invoice=' . ($invoiceid ?: '∅'));

        // --- 1) Filet: si pas de subid mais on a l'invoice, tenter de récupérer l'abo via l'API Stripe
        if (!$subid && $invoiceid && class_exists('\Stripe\Invoice')) {
            try {
                // NB: l’autoload/clé Stripe doit déjà être positionné ailleurs dans ton plugin
                $invoice = \Stripe\Invoice::retrieve($invoiceid, ['expand' => ['subscription']]);
                if (!empty($invoice->subscription)) {
                    $subid = is_object($invoice->subscription) ? $invoice->subscription->id : $invoice->subscription;
                    error_log("[subs][failed] recovered subid from invoice=$invoiceid -> $subid");
                }
            } catch (\Throwable $ex) {
                error_log('[subs][failed] cannot retrieve invoice '.$invoiceid.' : '.$ex->getMessage());
            }
        }
        if (!$subid) {
            error_log('[subs][failed] no provider_subscription_id, early return');
            return;
        }

        // --- 2) Retrouver la souscription locale liée à l’abo Stripe
        $sub = $DB->get_record('user_subscription', ['provider_subscription_id' => $subid], '*', IGNORE_MISSING);
        if (!$sub) {
            error_log('[subs][failed] no local sub for provider_sub='.$subid.', early return');
            return;
        }

        // --- 3) Idempotence par facture échouée
        //     On préfère 'last_failed_invoice_id' si présent, sinon fallback 'last_invoice_id'
        $idemField = property_exists($sub, 'last_failed_invoice_id') ? 'last_failed_invoice_id'
                    : (property_exists($sub, 'last_invoice_id') ? 'last_invoice_id' : null);
        if ($invoiceid && $idemField && !empty($sub->{$idemField}) && $sub->{$idemField} === $invoiceid) {
            error_log("[subs][failed] duplicate invoice_id=$invoiceid, idempotent return");
            return;
        }

        // --- 4) Mettre à jour l’état de la souscription (PAS d’extension d’accès ici)
        //     - payment_failed = 1
        //     - raison & prochain retry si dispo
        //     - last_failed_invoice_id (ou last_invoice_id si c’est tout ce qu’on a)
        if (property_exists($sub, 'payment_failed')) {
            $sub->payment_failed = 1;
        }
        if (property_exists($sub, 'last_payment_failed_at')) {
            $sub->last_payment_failed_at = time();
        }
        if ($invoiceid && $idemField) {
            $sub->{$idemField} = $invoiceid;
        }
        if ($failcode && property_exists($sub, 'last_payment_failed_reason')) {
            $sub->last_payment_failed_reason = substr((string)$failcode, 0, 255);
        }
        if ($nexttry && property_exists($sub, 'next_retry_at')) {
            $sub->next_retry_at = (int)$nexttry;
        }
        $sub->last_update = time();
        $DB->update_record('user_subscription', $sub);

        // --- 5) (Optionnel) Journaliser l’événement si ta table existe
        // --- LOG: facture échouée ---
        self::log_subscription_event(
            (int)$sub->id,
            'invoice_failed',
            $e->meta['invoice'] ?? null,
            [
                'provider'             => 'stripe',
                'provider_sub'         => $e->provider_subscription_id ?? null,
                'last_payment_error'   => $e->meta['last_payment_error'] ?? ($e->meta['failure_reason'] ?? null),
                'next_payment_attempt' => $e->meta['next_payment_attempt'] ?? null,
                'amount_minor'         => $e->amount_minor ?? null,
                'currency'             => $e->currency ?? null,
            ]
        );

        // --- 6) E-mail "paiement échoué" — robuste, ne doit JAMAIS casser le webhook
        try {
            if (class_exists('\local_subscriptions\mailer')) {
                $user    = \core_user::get_user($sub->userid, '*', MUST_EXIST); // user COMPLET
                $planrec = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', MUST_EXIST);

                // Deux noms possibles, suivant ton mailer
                if (method_exists('\local_subscriptions\mailer', 'send_failed_recurring')) {
                    \local_subscriptions\mailer::send_failed_recurring(
                        $user, $planrec, $sub, $amount, $currency, $invoiceid, $failcode, $nexttry
                    );
                } else {
                    error_log('[subs][failed] no mailer send_failed_recurring');
                }
            }
        } catch (\Throwable $ex) {
            error_log('[subs][mail][failed] '.$ex->getMessage());
            // On avale l’erreur : le webhook doit répondre 200 quoi qu’il arrive
        }

        // --- 7) Important : on NE suspend PAS les cours ici.
        //     L’accès reste actif jusqu’à end_date. À l’échéance, ta tâche expire_user_enrolments_task fera le ménage.
    }


    /** Annulation côté Stripe (immédiate ou à fin de période) */
    public static function on_subscription_canceled(InternalEvent $e): void {
        global $DB, $CFG;
        $subid = $e->provider_subscription_id ?? null;
        if (!$subid) return;

        $sub = $DB->get_record('user_subscription', ['provider_subscription_id' => $subid], '*', IGNORE_MISSING);
        if (!$sub) return;

        // On garde l'accès jusqu'à end_date, mais on marque status "canceled"
        $sub->status      = 'canceled';
        $sub->last_update = time();
        $DB->update_record('user_subscription', $sub);

        // (option) mail d'information
        // --- Mail "cancellation" ROBUSTE (ne doit jamais casser le webhook) ---
        try {
            if (class_exists('\local_subscriptions\mailer') && method_exists('\local_subscriptions\mailer', 'send_cancellation_info')) {
                // user COMPLET (tous les champs attendus par fullname())
                $user    = \core_user::get_user($sub->userid, '*', MUST_EXIST);
                $planrec = $DB->get_record('subscription_plan', ['id' => $sub->planid], '*', MUST_EXIST);

                // Optionnel : passer l’info "à la fin de période" si tu l’as dans l’évènement
                $atperiodend = null;
                if (!empty($e->meta['cancel_at_period_end'])) {
                    $atperiodend = (int)(bool)$e->meta['cancel_at_period_end'];
                }

                \local_subscriptions\mailer::send_cancellation_info($user, $planrec, $sub, $atperiodend);
            }
        } catch (\Throwable $ex) {
            error_log('[subs][mail][cancellation] '.$ex->getMessage());
            // On n'arrête jamais le flux webhook
        }

        // Si annulation immédiate -> suspendre tout de suite les accès
        $immediate = isset($e->meta['cancel_at_period_end']) ? !(bool)$e->meta['cancel_at_period_end'] : null;

        if ($immediate === true || ($sub->end_date && $sub->end_date < time())) {
            require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');
            \local_subscriptions\subscription_manager::suspend_user_in_plan_courses((int)$sub->userid, (int)$sub->planid);
        }
        // Sinon, on laisse actif jusqu’à end_date ; la tâche expire_user_enrolments_task suspendra à l’échéance.

        // --- LOG: abonnement annulé ---
        self::log_subscription_event(
            (int)$sub->id,
            'subscription_canceled',
            $e->meta['event_id'] ?? null,  // souvent null pour une annulation ; pas grave
            [
                'provider'             => 'stripe',
                'provider_sub'         => $e->provider_subscription_id ?? null,
                'cancel_at_period_end' => $e->meta['cancel_at_period_end'] ?? null,
                'current_period_end'   => $e->meta['current_period_end'] ?? null,
            ]
        );


    }

    /** Mise à jour de la souscription (upgrade/downgrade, pause, etc.) */
    /**
     * Synchronise la souscription locale suite à un customer.subscription.updated (ancre, CAPE, statut...).
     * - Étend end_date si current_period_end augmente (idempotent, on ne raccourcit jamais).
     * - (Ré)étend les accès cours immédiatement.
     * - Si cancel_at_period_end bascule 0→1 (et que le champ existe), envoie un mail d’information d’annulation "à la fin de période".
     * - Journalise l’événement dans subscription_event (si la table existe).
     */
    public static function on_subscription_updated(InternalEvent $e): void {
        global $DB, $CFG;

        $providersub = $e->provider_subscription_id ?? null;
        if (!$providersub) { return; }

        $rec = $DB->get_record('user_subscription', ['provider_subscription_id' => $providersub], '*', IGNORE_MISSING);
        if (!$rec) { return; }

        // Récup métadonnées utiles de l'event
        $cps  = isset($e->meta['current_period_start']) ? (int)$e->meta['current_period_start'] : 0;
        $cpe  = isset($e->meta['current_period_end'])   ? (int)$e->meta['current_period_end']   : 0;
        $cape = isset($e->meta['cancel_at_period_end']) ? (int)((bool)$e->meta['cancel_at_period_end']) : null;
        $stat = $e->meta['status'] ?? null;

        $updated = false;

        // 1) Étendre end_date si on a une info Stripe plus lointaine (jamais de raccourcissement)
        if ($cpe > 0 && ((int)$rec->end_date) < $cpe) {
            $rec->end_date   = $cpe;
            $rec->last_update = time();
            // On s'assure de rester 'active'
            if ($rec->status !== 'active') { $rec->status = 'active'; }
            $DB->update_record('user_subscription', $rec);
            $updated = true;

            // (Ré)étendre les accès cours immédiatement (idempotent)
            require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_manager.php');
            \local_subscriptions\subscription_manager::enrol_user_to_courses(
                (int)$rec->userid,
                (int)$rec->planid,
                (int)($rec->start_date ?: time()),
                (int)$rec->end_date
            );
        }

        // 2) Gestion du cancel_at_period_end (CAPE)
        //    - si ton schéma possède un champ 'cancel_at_period_end', on l'alimente et on notifie UNIQUEMENT s'il bascule 0→1
        $shouldnotifycape = false;
        if ($cape !== null && property_exists($rec, 'cancel_at_period_end')) {
            $prevcape = (int)($rec->cancel_at_period_end ?? 0);
            if ($prevcape !== $cape) {
                $rec->cancel_at_period_end = $cape;
                $rec->last_update = time();
                $DB->update_record('user_subscription', $rec);
                $updated = true;
                if ($prevcape === 0 && $cape === 1) {
                    $shouldnotifycape = true; // bascule 0→1 → informer l'utilisateur
                }
            }
        }

        // 3) Email d’information si CAPE vient d’être activé (0→1)
        if ($shouldnotifycape) {
            try {
                if (class_exists('\local_subscriptions\mailer') && method_exists('\local_subscriptions\mailer', 'send_cancellation_info')) {
                    $user    = \core_user::get_user($rec->userid, '*', MUST_EXIST); // user complet
                    $planrec = $DB->get_record('subscription_plan', ['id' => $rec->planid], '*', MUST_EXIST);

                    // On passe atperiodend=1 et un hint de fin (cpe) si dispo pour remplir la "Période"
                    \local_subscriptions\mailer::send_cancellation_info(
                        $user,
                        $planrec,
                        $rec,
                        1,          // atperiodend
                    );
                }
            } catch (\Throwable $ex) {
                error_log('[subs][mail][cape_notice] '.$ex->getMessage());
                // On ne casse jamais le flux
            }
        }

        // 4) Journaliser l’update (même s’il n’y a pas eu de modif locale, pour audit)
        self::log_subscription_event(
            (int)$rec->id,
            'subscription_updated',
            $e->meta['event_id'] ?? null,   // <= on logge l'id de l'event Stripe
            [
                'provider'               => 'stripe',
                'provider_sub'           => $providersub,
                'status'                 => $stat,
                'current_period_start'   => $cps ?: null,
                'current_period_end'     => $cpe ?: null,
                'cancel_at_period_end'   => $cape,
                'applied'                => $updated ? 1 : 0,
            ]
        );
    
    }


    // --- ADD: logger centralisé pour subscription_event ---
    private static function log_subscription_event(int $subscriptionid, string $eventtype, ?string $providerid, array $meta = []): void {
        global $DB;
        // Par sécurité (dev / upgrade en cours)
        if (!$DB->get_manager()->table_exists('subscription_event')) {
            return;
        }
        $rec = (object)[
            'subscriptionid'    => $subscriptionid,
            'eventtype'         => $eventtype,          // ex: invoice_paid, invoice_failed, subscription_canceled
            'provider_event_id' => $providerid,         // ex: invoice id (in_...), sinon null
            'occurred_at'       => time(),
            'payload_json'      => json_encode($meta ?? [], JSON_UNESCAPED_UNICODE),
        ];
        $DB->insert_record('subscription_event', $rec);
    }


}
