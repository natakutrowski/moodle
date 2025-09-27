<?php
namespace local_subscriptions\payment;

use stdClass;

final class ProviderSelector {
    /**
     * Choisit le provider à partir du plan, de la currency, ou d’une règle business.
     * Ex: RUB ou pays=RU -> ALFA; sinon STRIPE.
     */
    public static function chooseForPlan(stdClass $plan, string $currency, ?int $userid = null): string {
        $cur = strtoupper($currency);
        if ($cur === 'RUB') return Provider::ALFA;

        // Règle simple RU: si un jour tu stockes le pays utilisateur, on peut router ici.
        // if ($userid && self::isRussianUser($userid)) return Provider::ALFA;

        // Par défaut Stripe
        return Provider::STRIPE;
    }

    /** Liste blanche minimaliste des providers supportés. Ajuste si tu en ajoutes. */
    private static function allowed_providers(): array {
        return [Provider::STRIPE, Provider::ALFA];
    }

    /** Nettoie/normalise le provider (lowercase + whitelist). Fallback 'stripe'. */
    private static function sanitize_provider(?string $p, string $fallback = Provider::STRIPE): string {
        $p = $p !== null ? trim(mb_strtolower($p)) : '';
        if (in_array($p, self::allowed_providers(), true)) {
            return $p;
        }
        return $fallback;
    }

    /**
     * Résout le provider depuis le contexte disponible (PR / évènement / sub / defaults).
     * Ordre de priorité:
     *  1) $pr->provider           (champ provider de la PR si tu l'as ajouté)
     *  2) $pr->payment_provider   (si tu l'écris côté create_session)
     *  3) $eventMeta['provider']  (InternalEvent)
     *  4) $sub->payment_provider  (si tu réutilises une sub existante)
     *  5) $default                (stripe)
     */
    public static function resolve_provider(
        ?stdClass $pr = null,
        ?array $eventMeta = null,
        ?stdClass $sub = null,
        string $default = Provider::STRIPE
    ): string {
        $cand = null;

        if ($pr && !empty($pr->provider))          { $cand = (string)$pr->provider; }
        elseif ($pr && !empty($pr->payment_provider)) { $cand = (string)$pr->payment_provider; }
        elseif ($eventMeta && !empty($eventMeta['provider'])) { $cand = (string)$eventMeta['provider']; }
        elseif ($sub && !empty($sub->payment_provider)) { $cand = (string)$sub->payment_provider; }

        return self::sanitize_provider($cand, $default);
    }



}
