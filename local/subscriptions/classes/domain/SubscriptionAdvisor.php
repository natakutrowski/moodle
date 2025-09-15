<?php
namespace local_subscriptions\domain;

defined('MOODLE_INTERNAL') || die();

class SubscriptionAdvisor {

    /** Nombre de prolongations d’1 an en file (queued) dans le même scope. */    
    private static function count_scope_queued_one_year(int $userid, int $scopeid): int {
        global $DB;
        return (int)$DB->get_field_sql("
            SELECT COUNT(1)
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.userid = :u
            AND p.accessscopeid = :scope
            AND p.duration_key = '1year'
            AND s.status = 'queued'
        ", ['u'=>$userid, 'scope'=>$scopeid]);
    }

    /** Fin la plus tardive (active+queued) dans le même scope. */    
    public static function max_scope_end(int $userid, int $scopeid): int {
        global $DB;
        return (int)$DB->get_field_sql("
            SELECT COALESCE(MAX(s.end_date),0)
            FROM {user_subscription} s
            JOIN {subscription_plan} p ON p.id = s.planid
            WHERE s.userid = :u
            AND p.accessscopeid = :scope
            AND s.status IN ('active','queued')
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
               AND s.status = :active
               AND s.end_date >= :now
          ORDER BY s.end_date DESC
        ", ['u' => $userid, 'active' => 'active', 'now' => $now]);

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
                AND status IN ('active','queued')
            ", ['u'=>$userid, 'p'=> (int)$targetplanid]);

            $activation = $lastend ? userdate(((int)$lastend) + 1) : userdate(time());

            $opts[] = [
                'key'       => 'queue_future',
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
                'key'       => 'queue_future',
                'label'     => get_string('option_queue_future', 'local_subscriptions', $activation),
                'amount'    => (float)$targetprice->price,
                'currency'  => $currency,
                'ref_subid' => (int)$sameScopeActive->id,
                'extra'     => ['anchor_end' => (int)$scope_max_end]
            ];

            // 2) Upgrade — règles : pas d’upgrade si 2 prolongations d’1 an déjà en file
            //    ou si montant d’upgrade <= 0
            if ($sameCurrency && $t0) {
                $P1 = self::find_scope_duration_price($scopeid, '1year',   $currency);
                $P3 = self::find_scope_duration_price($scopeid, '3years',  $currency);

                if ($P1 !== null && $P3 !== null) {
                    $D1 = self::duration_to_seconds('1year');
                    $D3 = self::duration_to_seconds('3years');
                    $upgrade = self::compute_upgrade_amount_equitable($P1, $P3, $t0, time(), $D1, $D3);

                    // Crédit des prolongations déjà payées (file)
                    $queuedTotalPaid = (float)$DB->get_field_sql("
                        SELECT COALESCE(SUM(s.pricepaid),0)
                        FROM {user_subscription} s
                        JOIN {subscription_plan} p ON p.id = s.planid
                        WHERE s.userid = :u
                        AND p.accessscopeid = :scope
                        AND s.status = 'queued'
                    ", ['u'=>$userid, 'scope'=>$scopeid]);

                    $queuedOneYearCount = self::count_scope_queued_one_year($userid, $scopeid);
                    $upgradeNowAmount   = max(0.0, round($upgrade - $queuedTotalPaid, 2));

                    $blockUpgrade = ($queuedOneYearCount >= 2) || ($upgradeNowAmount <= 0.0);

                    if (!$blockUpgrade) {
                        // IDs des queued à remplacer (pour la file)
                        $queuedIds = $DB->get_fieldset_sql("
                            SELECT s.id
                            FROM {user_subscription} s
                            JOIN {subscription_plan} p ON p.id = s.planid
                            WHERE s.userid = :u
                            AND p.accessscopeid = :scope
                            AND s.status = 'queued'
                        ORDER BY s.end_date ASC
                        ", ['u'=>$userid, 'scope'=>$scopeid]);

                        // (a) Upgrade maintenant : remplace la file
                        $opts[] = [
                            'key'       => 'upgrade_now_replace_chain',
                            'label'     => get_string('option_upgrade_now_replace', 'local_subscriptions'),
                            'amount'    => $upgradeNowAmount,
                            'currency'  => $currency,
                            'ref_subid' => (int)$sameScopeActive->id,
                            'extra'     => ['queued_ids' => $queuedIds],
                        ];
                    }
                }
            }

            return $opts;
        }


        // C) Aucun abonnement actif -> achat standard
        $opts[] = [
            'key'       => 'purchase_new',
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

    public static function find_scope_duration_price(int $scopeid, string $duration_key, string $currency): ?float {
        global $DB;
        $plan = $DB->get_record('subscription_plan', [
            'accessscopeid' => $scopeid,
            'duration_key'  => $duration_key,
            'is_active'     => 1
        ], '*', IGNORE_MISSING);
        if (!$plan) return null;
        $price = $DB->get_record('subscription_plan_price', [
            'planid'   => $plan->id,
            'currency' => $currency
        ], '*', IGNORE_MISSING);
        return $price ? (float)$price->price : null;
    }

    public static function compute_upgrade_amount_equitable(
        float $P1, float $P2, int $t0, int $now, int $D1, int $D2
    ): float {
        $t = max(0, min($D2, $now - $t0));
        $part2 = $P2 * (($D2 - $t) / $D2);
        $part1 = $P1 * ($t / $D1);
        $amount = $part2 - $part1;
        return max(0.0, round($amount, 2));
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
}
