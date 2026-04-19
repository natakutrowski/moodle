<?php
namespace local_subscriptions\domain;

use local_subscriptions\constants\Operation;
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
            AND s.end_date >= :now
            ORDER BY s.end_date DESC
        ", ['u' => $userid, 'status' => Status::ACTIVE, 'now' => $now]);

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

/*         // A) Même plan -> UNIQUEMENT "prolonger à la suite"
        if ($samePlanActive) {
            // Date d’activation = fin la plus lointaine (active OU déjà queued)
            $lastend = $DB->get_field_sql("
                SELECT MAX(end_date)
                FROM {user_subscription}
                WHERE userid = :u
                AND planid = :p
                AND status IN ('".Status::ACTIVE."','".Status::QUEUED."')
            ", ['u' => $userid, 'p' => (int)$targetplanid]);

            $activation = $lastend ? userdate(((int)$lastend) + 1) : userdate($now);

            $base = (float)$targetprice->price;

            // Remise de renouvellement (fin d’abonnement + fenêtre)
            $renew = self::compute_renew_discount($samePlanActive, $now, $base);

            $opts[] = [
                'key'       => Operation::QUEUE_FUTURE,
                'label'     => get_string('option_queue_future', 'local_subscriptions', $activation),
                'amount'    => $renew['final'],          // prix remisé envoyé à Stripe/Alfa
                'currency'  => $currency,
                'ref_subid' => (int)$samePlanActive->id,
                'extra'     => [
                    'anchor_end'        => (int)$lastend,
                    'discount_percent'  => $renew['percent'],
                    'discount_amount'   => $renew['amount'],
                    'list_price'        => $base,
                ],
            ];
            return $opts;
        }

        // B) Plan différent mais même scope -> proposer UPGRADE(s) + PROLONGER (file)
        if ($sameScopeActive) {
            $sameCurrency = !empty($sameScopeActive->currency)
                && \core_text::strtolower($sameScopeActive->currency) === \core_text::strtolower($currency);

            $t0 = self::find_scope_first_start($userid, $scopeid);

            // Fin la plus tardive dans TOUT le scope (active + queued), pas seulement le plan cible
            $scope_max_end = self::max_scope_end($userid, $scopeid);

            $opts = [];

            // 1) Prolonger (dans le plan cible) : activation au-delà de la dernière brique scope
            $activation = $scope_max_end ? userdate($scope_max_end + 1) : userdate($now);
            $base       = (float)$targetprice->price;

            // même règle de remise que ci-dessus, basée sur la sub active du scope
            $renew = self::compute_renew_discount($sameScopeActive, $now, $base);

            $opts[] = [
                'key'       => Operation::QUEUE_FUTURE,
                'label'     => get_string('option_queue_future', 'local_subscriptions', $activation),
                'amount'    => $renew['final'],
                'currency'  => $currency,
                'ref_subid' => (int)$sameScopeActive->id,
                'extra'     => [
                    'anchor_end'        => (int)$scope_max_end,
                    'discount_percent'  => $renew['percent'],
                    'discount_amount'   => $renew['amount'],
                    'list_price'        => $base,
                ],
            ];


            // 2) Upgrade générique (fenêtre complète)
            if ($sameCurrency && $t0) {
                $currplan = $DB->get_record('subscription_plan', ['id' => $sameScopeActive->planid], '*', MUST_EXIST);

                $quote = self::quote_upgrade($sameScopeActive, $currplan, $targetplan, $currency);
                if (!empty($quote['allowed']) && $quote['amount'] > 0) {

                $upgradeBase = (float)$quote['amount']; // montant avant promo = base_total - déjà_payé
                $upgradeFinal = $upgradeBase;

                $discPctCfg = (int)(get_config('local_subscriptions','trial_discount_percent') ?? 0);
                $hasDisc    = $discPctCfg > 0 && \local_subscriptions\trial_manager::is_discount_window_open($userid);
                if ($hasDisc) {
                    $upgradeFinal = round($upgradeBase * (100 - $discPctCfg) / 100, 2);
                }

                $opts[] = [
                    'key'       => Operation::UPGRADE_NOW_REPLACE_CHAIN,
                    'label'     => get_string('option_upgrade_now_replace', 'local_subscriptions'),
                    'amount'    => $upgradeFinal,
                    'currency'  => $currency,
                    'ref_subid' => (int)$sameScopeActive->id,
                    'extra'     => [
                        'upgrade_window'  => $quote['window'],
                        'replace_ids'     => $quote['replace_ids'],
                        'spent_window'    => $quote['spent_window'],
                        'target_price'    => $quote['target_price'],

                        // nouveau breakdown plus clair
                        'upgrade_breakdown'      => $quote['breakdown'],  // contient P1, P2, base_total, part_past, part_future…
                        'upgrade_base_amount'    => $upgradeBase,         // avant promo
                        'discount_percent'       => $hasDisc ? $discPctCfg : 0,
                        'upgrade_final_amount'   => $upgradeFinal,        // montant proposé
                    ],
                ];

                }
            }



            // 3) Prolonger (dans le plan cible) : activation au-delà de la dernière brique scope
            return $opts;

        }
 */
        // C) Aucun abonnement actif -> achat standard
        $opts[] = [
            'key'       => Operation::PURCHASE_NEW,
            'label'     => get_string('option_purchase_new', 'local_subscriptions'),
            'amount'    => (float)$targetprice->price,
            'currency'  => $currency,
            'ref_subid' => null,
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


}
