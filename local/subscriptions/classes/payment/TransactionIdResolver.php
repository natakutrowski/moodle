<?php
namespace local_subscriptions\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\Provider;
use local_subscriptions\payment\stripe\StripeTransactionIdResolver;
use local_subscriptions\payment\alfa\AlfaTransactionIdResolver;

class TransactionIdResolver {

    /**
     * Résout un transactionid standardisé pour un paiement.
     *
     * @param string $provider   ex: 'stripe', 'alfa'
     * @param array  $meta       métadonnées de l’événement (si dispo)
     * @param array|null $payload payload brut du PSP (décodé du JSON si dispo)
     * @return ?string transaction id ou null si introuvable
     */
    public static function resolve(string $provider, array $meta = [], ?array $payload = null): ?string {
        $provider = strtolower($provider);
        switch ($provider) {
            case Provider::STRIPE:
                return StripeTransactionIdResolver::resolve($meta, $payload);

            case Provider::ALFA:
                return AlfaTransactionIdResolver::resolve($meta, $payload);

            default:
                return null;
        }
    }

    /**
     * Sugar: résout depuis un enregistrement SPR (subscription_payment_request).
     * Suppose des colonnes: payment_provider, response_json.
     */
    public static function resolve_from_spr(\stdClass $spr, array $meta = []): ?string {
        $payload = null;
        if (!empty($spr->response_json)) {
            $payload = json_decode($spr->response_json, true);
            if (!is_array($payload)) { $payload = null; }
        }
        return self::resolve((string)$spr->payment_provider, $meta, $payload);
    }
}
