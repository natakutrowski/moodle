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

    public static function advise_options(int $userid, int $targetplanid, string $currency): array {
        global $DB;

        $now        = time();
        $targetplan = $DB->get_record('subscription_plan', ['id' => $targetplanid, 'is_active' => 1], '*', MUST_EXIST);
        $scopeid    = (int)$targetplan->accessscopeid;

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

        // A) Même plan -> UNIQUEMENT "prolonger à la suite"
        if ($samePlanActive) {
            // Date d’activation = fin la plus lointaine (active OU déjà queued)
            $lastend = $DB->get_field_sql("
                SELECT MAX(end_date)
                FROM {user_subscription}
                WHERE userid = :u
                AND planid = :p
                AND status IN ('".Status::ACTIVE."','".Status::QUEUED."')
            ", ['u'=>$userid, 'p'=> (int)$targetplanid]);

            $activation = $lastend ? userdate(((int)$lastend) + 1) : userdate(time());

            $opts[] = [
                'key'       => Operation::QUEUE_FUTURE,
                'label'     => get_string('option_queue_future', 'local_subscriptions', $activation),
                'amount'    => (float)$targetprice->price,
                'currency'  => $currency,
                'ref_subid' => (int)$samePlanActive->id,
                'extra'     => ['anchor_end' => (int)$lastend] // utile pour l’UI/PR
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
            $activation = $scope_max_end ? userdate($scope_max_end + 1) : userdate(time());
            $opts[] = [
                'key'       => Operation::QUEUE_FUTURE,
                'label'     => get_string('option_queue_future', 'local_subscriptions', $activation),
                'amount'    => (float)$targetprice->price,
                'currency'  => $currency,
                'ref_subid' => (int)$sameScopeActive->id,
                'extra'     => ['anchor_end' => (int)$scope_max_end]
            ];

            // 2) Upgrade générique (fenêtre complète) :
            // Montant = P2*(D2 - t)/D2 + P1*(t/D1) - sum_paid_in_window
            if ($sameCurrency && $t0) {
                $currplan = $DB->get_record('subscription_plan', ['id' => $sameScopeActive->planid], '*', MUST_EXIST);

                $P1 = self::plan_price_in_currency((int)$currplan->id,   $currency);
                $P2 = self::plan_price_in_currency((int)$targetplan->id,  $currency);
                if ($P1 !== null && $P2 !== null) {
                    $D1 = self::duration_to_seconds($currplan->duration_key  ?? '1year');
                    $D2 = self::duration_to_seconds($targetplan->duration_key ?? '1year');

                    // Fenêtre de référence : [t0 ; t0 + D2)
                    $t0sec   = (int)$t0; // tu le calcules déjà plus haut
                    $tNow    = time();
                    $t       = max(0, min($D2, $tNow - $t0sec)); // consommation depuis t0, bornée à D2

                    $base = round( ($P2 * ($D2 - $t) / $D2) + ($P1 * ($t / $D1)), 2 );

                    // Somme déjà payée dans la fenêtre (inclut expirés/replaced/active/queued)
                    //$scopeid  = $scopeid; // déjà calculé plus haut
                    $spentWin = self::sum_window_spent_in_currency((int)$sameScopeActive->userid, (int)$scopeid,
                                                                $t0sec, $t0sec + $D2, $currency);

                    $upgradeAmount = round($base - $spentWin, 2);

                    // Règle dégressive stricte: ne pas proposer si déjà payé >= prix du plan cible
                    if ($spentWin < $P2 && $upgradeAmount > 0) {

                        // Bricks à remplacer : uniquement active/queued chevauchant la fenêtre
                        $toReplace = self::list_scope_overlaps((int)$sameScopeActive->userid, (int)$scopeid,
                                                            $t0sec, $t0sec + $D2, [Status::ACTIVE,Status::QUEUED]);
                        $replaceIds = array_map(fn($s)=> (int)$s->id, $toReplace);

                        $opts[] = [
                            'key'       => Operation::UPGRADE_NOW_REPLACE_CHAIN,
                            'label'     => get_string('option_upgrade_now_replace', 'local_subscriptions'),
                            'amount'    => (float)$upgradeAmount,
                            'currency'  => $currency,
                            'ref_subid' => (int)$sameScopeActive->id,
                            'extra'     => [
                                'upgrade_window' => ['start' => $t0sec, 'end' => $t0sec + $D2],
                                'replace_ids'    => $replaceIds,
                                'spent_window'   => $spentWin,
                                'target_price'   => $P2,
                                't_consumed_sec' => $t,
                                'base_formula'   => '(P2*(D2-t)/D2) + (P1*(t/D1)) - spent_window'
                            ],
                        ];
                    }
                }
            }


            // 3) Prolonger (dans le plan cible) : activation au-delà de la dernière brique scope
            return $opts;

        }

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

        return [
            'amount'  => $amount,
            'allowed' => true,
            'breakdown' => [
                'rate_current_per_s' => $D1 ? round($P1 / $D1, 10) : 0.0,
                'rate_target_per_s'  => $D2 ? round($P2 / $D2, 10) : 0.0,
                'used_sec'           => $t,
                'remain_sec'         => max(0, $D2 - $t),
                'part_remaining'     => round($partPast, 2),    // « passée »
                'part_extension'     => round($partFuture, 2),  // « à venir »
                'base'               => $base,
            ],
            'window'        => ['start'=>$winStart, 'end'=>$winEnd],
            'replace_ids'   => $replaceIds,
            'spent_window'  => $spentWin,
            'target_price'  => $P2,
        ];
    }

}
