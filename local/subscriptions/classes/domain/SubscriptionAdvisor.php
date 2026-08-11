<?php
namespace local_subscriptions\domain;

use local_subscriptions\constants\Operation;
use local_subscriptions\commerce\upgrade\CommercePlanOwnershipResolver;
use local_subscriptions\constants\Status;

defined('MOODLE_INTERNAL') || die();

class SubscriptionAdvisor {

    /** Fin la plus tardive (active+queued) dans le même scope. */    
    public static function max_scope_end(int $userid, int $scopeid): int {
        global $DB;
        return (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(s.end_date),0)
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.userid = :u
            AND p.accessscopeid = :scope
            AND s.status IN ('".Status::ACTIVE."','".Status::QUEUED."')
        ", ['u'=>$userid, 'scope'=>$scopeid]);
    }

    /**
     * Fenêtre spéciale de remise pour les PROLONGATIONS :
     * - on réutilise trial_discount_percent
     * - ouverte si :
     *   a) trial_manager::is_discount_window_open() est vrai
     *   OU
     *   b) on est dans les 7 jours suivant la fin de la souscription active.
     */
    private static function is_prolongation_discount_open(int $userid, \stdClass $activesub): bool {
        $discPct = (int)(get_config('local_subscriptions', 'trial_discount_percent') ?? 0);
        if ($discPct <= 0) {
            return false;
        }

        // 1) Fenêtre classique (période d’essai, etc) : on la respecte.
        if (\local_subscriptions\trial_manager::is_discount_window_open($userid)) {
            return true;
        }

        // 2) Fenêtre « post-abonnement » pour prolonger:
        $graceDays = (int)(get_config('local_subscriptions', 'prolong_discount_grace_days') ?? 7);
        $limit     = ((int)$activesub->end_date) + $graceDays * DAYSECS;

        return time() <= $limit;
    }


    public static function advise_options(int $userid, int $targetplanid, string $currency): array {
        global $DB;

        $now        = time();
        $targetplan = $DB->get_record('subscription_plan', ['id' => $targetplanid], '*', MUST_EXIST);
        $scopeid    = (int)$targetplan->accessscopeid;

        // Si le plan est inactif, on ne propose pas d’options ici : on laisse subscribe.php
        // afficher les plans actifs du même scope.
        if (empty($targetplan->is_active)) {
            throw new \moodle_exception('plan_inactive', 'local_subscriptions', '',
                (object)['scopeid' => $scopeid, 'planid' => $targetplanid]);
        }
        $targetprice = $DB->get_record('subscription_plan_price', ['planid' => $targetplanid, 'currency' => $currency], '*', IGNORE_MISSING);
        if (!$targetprice) { return []; }

        $activesubs = $DB->get_records_sql("
            SELECT s.*
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.userid = :u
            AND s.status = :status
            AND (s.end_date = 0 OR s.end_date >= :now)
        ORDER BY s.end_date DESC
        ", ['u' => $userid, 'status' => Status::ACTIVE, 'now' => $now]);

        // Native purchases may own the source plan without creating a Legacy user_subscription.
        // Add synthetic ownership records only for active upgrade rules targeting this plan.
        $knownplanids = [];
        foreach ($activesubs as $subscription) {
            $knownplanids[(int)$subscription->planid] = true;
        }
        $ownershipresolver = new CommercePlanOwnershipResolver($DB);
        foreach ($DB->get_records('subscription_plan_upgrade', [
            'toplanid' => $targetplanid,
            'isactive' => 1,
        ], 'id ASC') as $upgraderule) {
            $sourceplanid = (int)$upgraderule->fromplanid;
            if ($sourceplanid <= 0 || isset($knownplanids[$sourceplanid])) {
                continue;
            }
            $ownership = $ownershipresolver->resolve($userid, $sourceplanid);
            if ($ownership === null || !str_starts_with((string)$ownership['source'], 'native_')) {
                continue;
            }
            $synthetic = (object)[
                'id' => 0,
                'userid' => $userid,
                'planid' => $sourceplanid,
                'start_date' => (int)$ownership['startdate'],
                'end_date' => (int)$ownership['enddate'],
                'status' => Status::ACTIVE,
                '_commerce_source' => (string)$ownership['source'],
            ];
            $activesubs['native_plan_' . $sourceplanid] = $synthetic;
            $knownplanids[$sourceplanid] = true;
        }

        $samePlanActive  = null;
        $sameScopeActive = null;

        foreach ($activesubs as $s) {
            if ((int)$s->planid === $targetplanid) { $samePlanActive = $s; break; }
        }
        if (!$samePlanActive) {
            foreach ($activesubs as $s) {
                $p = $DB->get_record('subscription_plan', ['id' => $s->planid], 'id,accessscopeid', MUST_EXIST);
                if ((int)$p->accessscopeid === $scopeid) { $sameScopeActive = $s; break; }
            }
        }

        $opts = [];

        // B) Upgrade explicite configuré via subscription_plan_upgrade.
        // Exemple : A2 Grammar -> A2 Full.
        // Prix = prix du plan cible - prix du plan source, dans la même devise.
        foreach ($activesubs as $s) {
            $upgrade = $DB->get_record('subscription_plan_upgrade', [
                'fromplanid' => (int)$s->planid,
                'toplanid'   => $targetplanid,
                'isactive'   => 1,
            ], '*', IGNORE_MISSING);

            $fromplan = $DB->get_record('subscription_plan', ['id' => (int)$s->planid], 'id, name', IGNORE_MISSING);
            $toplan = $DB->get_record('subscription_plan', ['id' => $targetplanid], 'id, name', IGNORE_MISSING);

            if (!$upgrade) {
                continue;
            }

            if (($upgrade->pricingmode ?? '') !== 'difference') {
                continue;
            }

            $sourceprice = self::plan_price_in_currency((int)$s->planid, $currency);
            $targetpricevalue = self::plan_price_in_currency($targetplanid, $currency);

            if ($sourceprice === null || $targetpricevalue === null) {
                continue;
            }

            $baseamount = max(0, round((float)$targetpricevalue - (float)$sourceprice, 2));

            $discountpercent = self::get_trial_discount_percent($userid);
            $amount = $baseamount;

            if ($discountpercent > 0) {
                $amount = round($baseamount * (1 - ($discountpercent / 100)), 2);
            }

            if ($amount <= 0) {
                continue;
            }

            $opts[] = [
                'key'       => Operation::UPGRADE_NOW_REPLACE_CHAIN,
                'label'     => get_string('option_upgrade_difference', 'local_subscriptions'),
                'summary'   => get_string('upgrade_from_to_summary', 'local_subscriptions', [
                    'from' => $fromplan ? $fromplan->name : '',
                    'to'   => $toplan ? $toplan->name : '',
                ]),
                'badge'     => get_string('upgrade_badge', 'local_subscriptions'),
                'amount'    => $amount,
                'currency'  => $currency,
                'ref_subid' => (int)$s->id > 0 ? (int)$s->id : null,
                'extra'     => [
                    'upgrade_type' => 'plan_difference',
                    'from_planid'  => (int)$s->planid,
                    'to_planid'    => $targetplanid,
                    'replace_ids'  => (int)$s->id > 0 ? [(int)$s->id] : [],

                    'source_price' => (float)$sourceprice,
                    'target_price' => (float)$targetpricevalue,

                    'upgrade_base_amount'  => $baseamount,
                    'upgrade_final_amount' => $amount,
                    'discount_percent'     => $discountpercent,

                    'upgrade_window' => [
                        'start' => (int)$s->start_date,
                        'end'   => (int)$s->end_date,
                    ],
                ],
            ];

            break;
        }

        // Si un upgrade a été trouvé, on le retourne immédiatement.
        // Ainsi on ne retombe pas sur l'achat standard.
        if (!empty($opts)) {
            return $opts;
        }

        // C) Aucun upgrade trouvé -> achat standard.
        $baseamount = (float)$targetprice->price;
        $amount = $baseamount;
        $discountpercent = 0;

        $isTrialPlan = !empty($targetplan->is_trial);

        if (!$isTrialPlan && \local_subscriptions\trial_manager::is_discount_window_open($userid)) {
            $discountpercent = (float)(get_config('local_subscriptions', 'trial_discount_percent') ?? 0);

            if ($discountpercent > 0) {
                $amount = round($baseamount * (1 - ($discountpercent / 100)), 2);
            }
        }

        $opts[] = [
            'key'       => Operation::PURCHASE_NEW,
            'label'     => get_string('option_purchase_new', 'local_subscriptions'),
            'amount'    => $amount,
            'currency'  => $currency,
            'ref_subid' => null,
            'extra'     => [
                'base_amount' => $baseamount,
                'final_amount' => $amount,
                'discount_percent' => $discountpercent,
            ],
        ];

        return $opts;
    }

    public static function find_scope_first_start(int $userid, int $scopeid): ?int {
        global $DB;
        $subs = $DB->get_records_sql("
            SELECT s.*
              FROM {user_subscription} s
              JOIN {subscription_plan} p ON p.id = s.planid
             WHERE s.userid = :u AND p.accessscopeid = :scope
          ORDER BY s.start_date ASC
        ", ['u' => $userid, 'scope' => $scopeid]);
        if (!$subs) return null;
        $first = reset($subs);
        return (int)$first->start_date;
    }

    public static function duration_to_seconds(string $key): int {
        switch ($key) {
            case '1week':   return (int)round(7 * 86400);
            case '1month':  return (int)round(30.4375 * 86400);
            case '3months': return (int)round(91.3125 * 86400);
            case '6months': return (int)round(182.625 * 86400);
            case '1year':   return (int)round(365.25 * 86400);
            case '2years':  return (int)round(2 * 365.25 * 86400);
            case '3years':  return (int)round(3 * 365.25 * 86400);
            default:        return (int)round(365.25 * 86400);
        }
    }

    /** Prix d’un plan (devise du checkout) depuis mdl_subscription_plan_price (champ planid). */
    private static function plan_price_in_currency(int $planid, string $currency): ?float {
        global $DB;
        $rec = $DB->get_record('subscription_plan_price',
            ['planid' => $planid, 'currency' => mb_strtoupper($currency)], 'price', IGNORE_MISSING);
        if ($rec && isset($rec->price)) return (float)$rec->price;
        $any = $DB->get_records('subscription_plan_price', ['planid' => $planid], '', 'price', 0, 1);
        return $any ? (float)reset($any)->price : null;
    }

    /** Scope d’accès d’un plan. */
    public static function get_scope_id_for_plan(int $planid): ?int {
        global $DB;
        $plan = $DB->get_record('subscription_plan', ['id'=>$planid], 'id,accessscopeid', IGNORE_MISSING);
        return $plan ? (int)$plan->accessscopeid : null;
    }

    /** Souscriptions d’un scope qui CHEVAUCHENT [start,end) filtrées par statuts. */
    public static function list_scope_overlaps(int $userid, int $accessscopeid, int $start, int $end, array $statuses): array {
        global $DB;
        $planids = $DB->get_fieldset_select('subscription_plan', 'id', 'accessscopeid = :s', ['s'=>$accessscopeid]);
        if (empty($planids)) return [];
        list($inSql, $inParams) = $DB->get_in_or_equal($planids, SQL_PARAMS_NAMED, 'pid');
        list($stSql, $stParams) = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED, 'st');

        $sql = "SELECT *
                FROM {user_subscription}
                WHERE userid = :u
                AND planid  $inSql
                AND status  $stSql
                AND start_date < :endts
                AND end_date   > :startts
            ORDER BY start_date ASC, id ASC";
        $params = ['u'=>$userid, 'startts'=>$start, 'endts'=>$end] + $inParams + $stParams;
        return $DB->get_records_sql($sql, $params);
    }

    /** Somme “déjà dépensée” dans la fenêtre [start,end) du scope (devise $currency).
     *  On prend d’abord user_subscription.pricepaid si la devise matche ; sinon prix du plan. */
    private static function sum_window_spent_in_currency(int $userid, int $accessscopeid, int $start, int $end, string $currency): float {
        $subs = self::list_scope_overlaps($userid, $accessscopeid, $start, $end,
            [Status::ACTIVE,Status::QUEUED,Status::EXPIRED,Status::REPLACED]); // inclure expirés/replaced dans la fenêtre
        $sum = 0.0; $cache = [];
        foreach ($subs as $s) {
            $okcur = !empty($s->currency) && \core_text::strtolower($s->currency) === \core_text::strtolower($currency);
            if ($okcur && isset($s->pricepaid) && is_numeric($s->pricepaid)) {
                $sum += (float)$s->pricepaid;
            } else {
                $pid = (int)$s->planid;
                if (!isset($cache[$pid])) {
                    $cache[$pid] = self::plan_price_in_currency($pid, $currency) ?? 0.0;
                }
                $sum += (float)$cache[$pid];
            }
        }
        return round($sum, 2);
    }

    /**
     * Devis d’upgrade « fenêtre complète » (au niveau seconde) + règle dégressive :
     * Montant = P2*(D2−t)/D2 + P1*(t/D1) − somme_déjà_payée_dans_la_fenêtre
     * Fenêtre = [t0 ; t0 + D2), où t0 = début de chaîne du scope si dispo, sinon start de la sub active.
     * Bloque si somme_déjà_payée ≥ prix du plan cible.
     *
     * @return array{
     *   amount: float, allowed: bool, reason?: string,
     *   breakdown: array, window: array{start:int,end:int}, replace_ids:int[],
     *   spent_window: float, target_price: float
     * }
     */
    public static function quote_upgrade(object $currsub, object $currplan, object $targetplan, string $currency): array {
        global $DB;

        // Prix plans
        $P1 = self::plan_price_in_currency((int)$currplan->id,  $currency);
        $P2 = self::plan_price_in_currency((int)$targetplan->id, $currency);
        if ($P1 === null || $P2 === null) {
            return ['amount'=>0.0,'allowed'=>false,'reason'=>'missing_price',
                    'breakdown'=>[], 'window'=>['start'=>0,'end'=>0], 'replace_ids'=>[], 'spent_window'=>0.0, 'target_price'=>0.0];
        }

        // Durées (tes secondes approximées via duration_to_seconds)
        $D1 = self::duration_to_seconds($currplan->duration_key  ?? '1year');
        $D2 = self::duration_to_seconds($targetplan->duration_key ?? '1year');
        if ($D2 <= $D1) {
            return ['amount'=>0.0,'allowed'=>false,'reason'=>'shorter_or_equal_duration',
                    'breakdown'=>[], 'window'=>['start'=>0,'end'=>0], 'replace_ids'=>[], 'spent_window'=>0.0, 'target_price'=>$P2];
        }

        // Fenêtre : t0 = début de chaîne du scope si tu as cette méthode ; sinon fallback = début de la sub active
        $scopeid = self::get_scope_id_for_plan((int)$targetplan->id) ?? self::get_scope_id_for_plan((int)$currplan->id) ?? 0;
        $t0 = method_exists(__CLASS__, 'find_scope_first_start') && $scopeid
            ? (int)self::find_scope_first_start((int)$currsub->userid, (int)$scopeid)
            : (int)$currsub->start_date;

        $winStart = $t0;
        $winEnd   = $t0 + $D2;

        // Temps consommé depuis t0, borné à D2
        $tNow = time();
        $t    = max(0, min($D2, $tNow - $winStart));

        // Base = partie passée au tarif courant + partie à venir au tarif cible
        $partPast   = $D1 ? $P1 * ($t / $D1) : 0.0;           // P1 * t/D1
        $partFuture = $D2 ? $P2 * (($D2 - $t) / $D2) : 0.0;   // P2 * (D2 - t)/D2
        $base       = round($partPast + $partFuture, 2);

        // Somme déjà payée dans la fenêtre (toutes subs du scope dans [t0; t0+D2))
        $spentWin   = $scopeid ? self::sum_window_spent_in_currency((int)$currsub->userid, (int)$scopeid, $winStart, $winEnd, $currency) : 0.0;

        // Règle dégressive stricte
        if ($spentWin >= $P2) {
            return ['amount'=>0.0,'allowed'=>false,'reason'=>'already_spent_more_than_target',
                    'breakdown'=>[], 'window'=>['start'=>$winStart,'end'=>$winEnd], 'replace_ids'=>[],
                    'spent_window'=>$spentWin, 'target_price'=>$P2];
        }

        $amount = round($base - $spentWin, 2);
        if ($amount <= 0) {
            return ['amount'=>0.0,'allowed'=>false,'reason'=>'nonpositive_amount',
                    'breakdown'=>[], 'window'=>['start'=>$winStart,'end'=>$winEnd], 'replace_ids'=>[],
                    'spent_window'=>$spentWin, 'target_price'=>$P2];
        }

        // Bricks à remplacer : seules les active/queued qui chevauchent la fenêtre
        $toReplace = $scopeid ? self::list_scope_overlaps((int)$currsub->userid, (int)$scopeid, $winStart, $winEnd, [Status::ACTIVE,Status::QUEUED]) : [];
        $replaceIds = array_map(fn($s)=> (int)$s->id, $toReplace);

        $breakdown = [
            'P1'              => $P1,   // prix plan actuel
            'P2'              => $P2,   // prix plan cible
            'used_sec'        => $t,
            'remain_sec'      => max(0, $D2 - $t),
            'part_past'       => round($partPast, 2),    // partie déjà consommée au tarif actuel
            'part_future'     => round($partFuture, 2),  // partie restante au tarif du plan cible
            'base_total'      => $base,                  // total théorique = part_past + part_future
        ];


        return [
            'amount'  => $amount,
            'allowed' => true,
            'breakdown'    => $breakdown,
            'window'        => ['start'=>$winStart, 'end'=>$winEnd],
            'replace_ids'   => $replaceIds,
            'spent_window'  => $spentWin,
            'target_price'  => $P2,
        ];
    }

    /**
     * Calcule la remise de renouvellement pour une souscription donnée.
     *
     * - Utilise renew_discount_percent si défini, sinon trial_discount_percent.
     * - Fenêtre par défaut : 7 jours après la fin de la souscription.
     *
     * @param \stdClass|null $sub   user_subscription active (ou la plus récente)
     * @param int            $now   timestamp courant
     * @param float          $base  prix catalogue (sans remise)
     * @return array{percent:int, amount:float, final:float}
     */
    private static function compute_renew_discount(?\stdClass $sub, int $now, float $base): array {
        $base = (float)$base;
        if ($base <= 0 || !$sub) {
            return ['percent' => 0, 'amount' => 0.0, 'final' => $base];
        }

        // % de remise : renew_discount_percent > sinon trial_discount_percent > sinon 0
        $pct = get_config('local_subscriptions', 'renew_discount_percent');
        if ($pct === false || $pct === null || $pct === '') {
            $pct = get_config('local_subscriptions', 'trial_discount_percent');
        }
        $percent = (int)($pct ?: 0);
        if ($percent <= 0) {
            return ['percent' => 0, 'amount' => 0.0, 'final' => $base];
        }

        // Fenêtre de renouvellement (J + N jours). Par défaut 7.
        $w = (int)(get_config('local_subscriptions', 'renew_discount_window_days') ?? 7);
        if ($w <= 0) { $w = 7; }

        $deadline = (int)$sub->end_date + $w * DAYSECS;
        if ($now > $deadline) {
            // Fenêtre dépassée → pas de remise
            return ['percent' => 0, 'amount' => 0.0, 'final' => $base];
        }

        $final   = round($base * (100 - $percent) / 100, 2);
        $discAmt = max(0.0, round($base - $final, 2));

        return ['percent' => $percent, 'amount' => $discAmt, 'final' => $final];
    }

    public static function filter_plans_for_subscribe(int $userid, array $plans): array {
        global $DB;

        $now = time();
        $result = [];

        foreach ($plans as $plan) {
            $active = $DB->record_exists_select('user_subscription',
                'userid = :userid
                AND planid = :planid
                AND status = :status
                AND (end_date = 0 OR end_date >= :now)',
                [
                    'userid' => $userid,
                    'planid' => (int)$plan->id,
                    'status' => Status::ACTIVE,
                    'now' => $now,
                ]
            );

            if ($active) {
                continue;
            }

            if (self::user_has_higher_or_equal_access_for_plan($userid, (int)$plan->id)) {
                continue;
            }

            $result[$plan->id] = $plan;
        }

        return $result;
    }    

    private static function get_trial_discount_percent(int $userid): float {
        global $DB;

        if ($userid <= 0) {
            return 0;
        }

        // Exemple simple : discount global configuré.
        // Remplace par ta vraie source si tu as déjà un setting spécifique.
        $discount = (float)get_config('local_subscriptions', 'trial_discount_percent');

        if ($discount <= 0) {
            return 0;
        }

        // On applique seulement si l’utilisateur a/avait un trial actif ou utilisé.
        $hastrial = $DB->record_exists_select('user_subscription',
            'userid = :userid
            AND status = :status
            AND planid IN (
                SELECT id FROM {subscription_plan} WHERE is_trial = 1
            )',
            [
                'userid' => $userid,
                'status' => Status::ACTIVE,
            ]
        );

        return $hastrial ? $discount : 0;
    }

    public static function user_has_higher_or_equal_access_for_plan(int $userid, int $planid): bool {
        global $DB;

        if ($userid <= 0 || $planid <= 0) {
            return false;
        }

        $targetentitlements = $DB->get_records(
            'subscription_plan_entitlement',
            ['planid' => $planid],
            '',
            'id, courseid, priority'
        );

        if (empty($targetentitlements)) {
            return false;
        }

        $now = time();

        $activesubs = $DB->get_records_sql("
            SELECT s.*
            FROM {user_subscription} s
            WHERE s.userid = :userid
            AND s.status = :status
            AND (s.end_date = 0 OR s.end_date >= :now)
        ", [
            'userid' => $userid,
            'status' => Status::ACTIVE,
            'now' => $now,
        ]);

        if (empty($activesubs)) {
            return false;
        }

        foreach ($targetentitlements as $target) {
            $covered = false;

            foreach ($activesubs as $sub) {
                $activeentitlements = $DB->get_records(
                    'subscription_plan_entitlement',
                    ['planid' => (int)$sub->planid, 'courseid' => (int)$target->courseid],
                    '',
                    'id, courseid, priority'
                );

                foreach ($activeentitlements as $active) {
                    if ((int)$active->priority >= (int)$target->priority) {
                        $covered = true;
                        break 2;
                    }
                }
            }

            if (!$covered) {
                return false;
            }
        }

        return true;
    }

}
