<?php
define('NO_DEBUG_DISPLAY', true);
require_once(dirname(__DIR__, 3).'/config.php');

use local_subscriptions\payment\PaymentGatewayFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\EventRouter;
use local_subscriptions\url\UrlFactory;

// Params
$pid      = required_param('pid', PARAM_INT);
$result   = optional_param('result', 'success', PARAM_ALPHA);       // success | cancel
$provider = optional_param('provider', '', PARAM_ALPHANUMEXT);
$orderId  = optional_param('orderId', '', PARAM_RAW_TRIMMED);       // Alfa peut renvoyer orderId
$sessionId= optional_param('session_id', '', PARAM_RAW_TRIMMED);    // Stripe success ?session_id=...

global $DB;

// Charge la PR
$pr = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', MUST_EXIST);

// Déduis le provider si non fourni
if ($provider === '') {
    $provider = $pr->payment_provider ?: Provider::defaultProvider();
}
$provider = strtolower($provider);

// CANCEL — commun à tous
if ($result === 'cancel') {
    $target = UrlFactory::payment_cancel(['pid' => $pid]);
    while (ob_get_level()) { @ob_end_clean(); }
    \core\session\manager::write_close();
    header('Location: '.$target->out(false));
    exit;
}

// SUCCESS — par provider
if ($provider === Provider::ALFA) {
    // Construis le payload pour revalider chez Alfa (mode token → orderId obligatoire)
    $payload = [];
    if (!empty($orderId)) {
        $payload['orderId'] = (string)$orderId;
    } elseif (!empty($pr->sessionid)) {
        $payload['orderId'] = (string)$pr->sessionid;
    }
    // On garde orderNumber pour mapper payment_request_id côté gateway (non transmis à l’API en mode token)
    $payload['orderNumber'] = (string)$pid;

    $gateway = PaymentGatewayFactory::for(Provider::ALFA);

    try {
        $event = $gateway->parse_webhook(json_encode($payload, JSON_UNESCAPED_UNICODE), []);
        EventRouter::handle($event);
    } catch (\Throwable $e) {
        $DB->set_field('subscription_payment_request', 'last_error', 'return: '.$e->getMessage(), ['id' => $pid]);
        $target = UrlFactory::payment_error([
            'pid' => $pid, 'code' => 'alfa_return', 'msg' => $e->getMessage(),
        ]);
        while (ob_get_level()) { @ob_end_clean(); }
        \core\session\manager::write_close();
        header('Location: '.$target->out(false));
        exit;
    }

    if ($event->type === 'checkout_completed') {
        $params = ['pid' => $pid];
        if (!empty($pr->login_token) && !empty($pr->login_token_expires) && (int)$pr->login_token_expires >= time()) {
            $params['t'] = $pr->login_token;
        }
        $target = UrlFactory::payment_success($params);
    } else {
        $meta    = is_array($event->meta ?? null) ? $event->meta : [];
        $code    = (string)($meta['orderStatus'] ?? 'alfa_error');
        $message = (string)($meta['errorMessage'] ?? $meta['reason'] ?? get_string('alfa_not_paid', 'local_subscriptions'));
        $target  = UrlFactory::payment_error([
            'pid' => $pid, 'code' => $code, 'msg' => $message,
        ]);
    }

    while (ob_get_level()) { @ob_end_clean(); }
    \core\session\manager::write_close();
    header('Location: '.$target->out(false));
    exit;
}

if ($provider === Provider::STRIPE) {
    // Mémorise la session Stripe si fournie
    if ($sessionId && empty($pr->sessionid)) {
        $DB->set_field('subscription_payment_request', 'sessionid', $sessionId, ['id' => $pid]);
    }

    $params = ['pid' => $pid];
    if (!empty($pr->login_token) && !empty($pr->login_token_expires) && (int)$pr->login_token_expires >= time()) {
        $params['t'] = $pr->login_token;
    }
    $target = UrlFactory::payment_success($params);

    while (ob_get_level()) { @ob_end_clean(); }
    \core\session\manager::write_close();
    header('Location: '.$target->out(false));
    exit;
}

// Provider inconnu → erreur générique
$target = UrlFactory::payment_error([
    'pid' => $pid, 'code' => 'unknown_provider', 'msg' => s($provider),
]);
while (ob_get_level()) { @ob_end_clean(); }
\core\session\manager::write_close();
header('Location: '.$target->out(false));
exit;
