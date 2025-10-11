<?php
namespace local_subscriptions\payment\alfa;

defined('MOODLE_INTERNAL') || die();

class AlfaTransactionIdResolver {
    public static function resolve(array $meta = [], ?array $payload = null): ?string {
        // 1) Payload (callback/notify) – les clés les plus probables
        //    orderId (UUID Alfa), uniqueOrderId, payment.id (selon intégration),
        //    orderNumber est ton numéro interne -> pas pour transactionid.
        $candidates = [
            $payload['orderId']              ?? null,
            $payload['uniqueOrderId']        ?? null,
            $payload['payment']['id']        ?? null, // certaines intégrations
            $meta['orderId']                 ?? null,
            $meta['uniqueOrderId']           ?? null,
        ];
        foreach ($candidates as $c) {
            if (!empty($c) && is_string($c)) { return $c; }
        }

        // 2) Rien trouvé : null (on ne fait pas d'appel API ici pour rester agnostique)
        return null;
    }
}
