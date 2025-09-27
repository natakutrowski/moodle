<?php
namespace local_subscriptions\support;
defined('MOODLE_INTERNAL') || die();

/**
 * Utilitaires de durée "métier" (UTC) pour les abonnements.
 */
final class Duration
{

    /** Vérifie/normalise une duration_key selon subscription_config::PLAN_DURATION_KEYS. */
    public static function normalize_duration_key(?string $key): string {
        $key = $key ? trim(mb_strtolower($key)) : '';
        // Ajuste le namespace/nom réel de ta classe de config si différent :
        $allowed = \local_subscriptions\subscription_config::PLAN_DURATION_KEYS ?? [
            '1month','3months','6months','1year','3years','lifetime'
        ];
        if (!in_array($key, $allowed, true)) {
            // fallback le plus neutre chez toi (tu utilisais '1year' en default)
            return '1year';
        }
        return $key;
    }

    /** 
     * Ajoute une durée "métier" à un timestamp **UTC** et renvoie l'end UTC.
     * - Valide la clé contre subscription_config::PLAN_DURATION_KEYS
     * - Gère 'lifetime'
     */
    public static function add_duration_utc(int $startUtc, string $durationkey): int {
        // 1) valider la clé
        $allowed = \local_subscriptions\subscription_config::PLAN_DURATION_KEYS
            ?? ['1month','3months','6months','1year','3years','lifetime'];
        $key = trim(mb_strtolower($durationkey));
        if (!in_array($key, $allowed, true)) {
            $key = '1year';
        }

        // 2) lifetime -> politique recommandée: **0** = illimité (standard Moodle/enrol)
        if ($key === 'lifetime') {
            return 0; // DB: end_date=0  => "sans fin"
        }

        // 3) calcul en UTC
        $dt = (new \DateTimeImmutable('@'.$startUtc))->setTimezone(new \DateTimeZone('UTC'));
        return match ($key) {
            '1month'  => $dt->modify('+1 month')->getTimestamp(),
            '3months' => $dt->modify('+3 months')->getTimestamp(),
            '6months' => $dt->modify('+6 months')->getTimestamp(),
            '1year'   => $dt->modify('+1 year')->getTimestamp(),
            '3years'  => $dt->modify('+3 years')->getTimestamp(),
            default   => $dt->modify('+1 year')->getTimestamp(),
        };
    }
}