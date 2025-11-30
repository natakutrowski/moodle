<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Calcule les montants à payer de manière agnostique PSP.
 * Ne déclenche aucun appel au PSP ; pur calcul côté serveur.
 */
class pricing_manager {

    /** Prix catalogue du plan (dans la devise demandée). */
    public static function get_plan_price(int $planid, string $currency): float {
        global $DB;
        $currency = strtoupper(trim($currency ?: 'EUR'));

        // Table attendue : subscription_plan_price(plan_id, currency, price)
        $price = $DB->get_field('subscription_plan_price', 'price',
            ['planid' => $planid, 'currency' => $currency], IGNORE_MISSING);

        if ($price === false || $price === null) {
            // Laisse l'appelant gérer (ex: afficher message propre)
            throw new \moodle_exception('pricing_missing_price', 'local_subscriptions', '', $currency);
        }
        return (float)$price;
    }

    /** Vrai si la fenêtre de remise "essai" (ex: 72h) est ouverte pour l'utilisateur. */
    public static function is_trial_discount_open(int $userid): bool {
        require_once(__DIR__.'/trial_manager.php');
        return \local_subscriptions\trial_manager::is_discount_window_open($userid);
    }

    /** Retourne [percent, amount, reason] en fonction de l’état d’essai et des réglages. */
    public static function compute_trial_discount(int $userid, int $planid, float $baseprice): array {
        $percent = 0;
        $amount  = 0.0;
        $reason  = null;

        if (self::is_trial_discount_open($userid)) {
            $cfgpct = (int)(get_config('local_subscriptions','trial_discount_percent') ?? 15);
            if ($cfgpct > 0) {
                $percent = max(0, min(100, $cfgpct));
                $amount  = round(($baseprice * $percent) / 100.0, 2);
                $amount  = max(0.0, min($amount, round($baseprice, 2)));
                $reason  = 'trial72h';
            }
        }
        return ['percent' => $percent, 'amount' => $amount, 'reason' => $reason];
    }

    /**
     * Calcule le payable final pour (userid, planid, currency).
     * Retourne : [list_price, discount_percent, discount_amount, final_price, discount_reason].
     */
    public static function compute_payable(int $userid, int $planid, string $currency): array {
        $list = self::get_plan_price($planid, $currency);
        $disc = self::compute_trial_discount($userid, $planid, $list);

        $final = round(max(0.0, $list - $disc['amount']), 2);

        return [
            'list_price'       => round($list, 2),
            'discount_percent' => (int)$disc['percent'],
            'discount_amount'  => round($disc['amount'], 2),
            'final_price'      => $final,
            'discount_reason'  => $disc['reason'], // peut être null
        ];
    }

    public static function get_plan_price_or_fallback(int $planid, string $wanted, \moodle_database $DB): array {
        $rows = $DB->get_records('subscription_plan_price', ['planid'=>$planid]);
        $map  = []; foreach ($rows as $r){ $map[strtoupper($r->currency)] = (float)$r->price; }
        if (isset($map[$wanted])) return ['available'=>true,'currency'=>$wanted,'price'=>$map[$wanted]];
        $firstcur = array_key_first($map);
        return ['available'=>false,'currency'=>$firstcur,'price'=>$map[$firstcur]];
    }

}
