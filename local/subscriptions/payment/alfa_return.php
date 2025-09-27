<?php
require(__DIR__ . '/../../../config.php');

use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\EventRouter;
use local_subscriptions\payment\Provider;
use local_subscriptions\url\UrlFactory;

// Pas de require_login : on accepte les invités (checkout public).

$pid   = required_param('pid', PARAM_INT);
$failed = optional_param('fail', 0, PARAM_BOOL);

if ($failed) {
    // L’utilisateur a quitté/annulé le checkout (failUrl).
    redirect(UrlFactory::payment_cancel(['pid' => $pid]));
    exit;
}

// On route Alfa pour RUB dans ce MVP. (Si tu stockes provider dans le PR, récupère-le et passe-le à la factory.)
$gateway = PaymentGatewayFactory::for(Provider::ALFA);

// On valide via orderId (plus fiable) ; on passe aussi l'orderNumber pour logging.
$payload = json_encode([
    'orderId'     => (string)$pr->sessionid,
    'orderNumber' => (string)$pid,
]);
$event = $gateway->parse_webhook($payload, []);

// Logique centralisée (crée sub, mails, etc.)
EventRouter::handle($event);

// Décider de la page de sortie
if ($event->type === 'checkout_completed') {
    redirect(UrlFactory::payment_success(['pid' => $pid]));
} else {
    // Extraire code/message si dispo (pour cohérence avec Stripe)
    $meta     = is_array($event->meta ?? null) ? $event->meta : [];
    $code     = (string)($meta['orderStatus'] ?? 'alfa_error');
    $message  = (string)($meta['errorMessage'] ?? $meta['reason'] ?? 'Payment not completed');
    redirect(UrlFactory::Payment_error([
        'code'  => $code,
        'msg'   => $message,
    ]));
}
