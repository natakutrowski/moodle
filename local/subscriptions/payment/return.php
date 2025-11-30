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
$embedded = optional_param('embedded', 0, PARAM_BOOL);

/**
 * Redirige en sortant de l'iframe si embedded=1 :
 * - si embedded = 1 : renvoie une page HTML/JS qui fait window.top.location = $url
 * - sinon : header Location classique.
 */
function local_subscriptions_redirect_from_return(moodle_url $target, bool $embedded): void {
    $url = $target->out(false);

    // On ferme les buffers et la session, quoi qu'il arrive.
    while (ob_get_level()) { @ob_end_clean(); }
    \core\session\manager::write_close();

    if ($embedded) {
        // On force la redirection au niveau top (sortie de l'iframe)
        echo '<!doctype html><html><head><meta charset="utf-8"><script>
                try {
                    if (window.top && window.top !== window) {
                        window.top.location.href = '.json_encode($url).';
                    } else {
                        window.location.href = '.json_encode($url).';
                    }
                } catch(e) {
                    window.location.href = '.json_encode($url).';
                }
              </script></head><body></body></html>';
        exit;
    } else {
        header('Location: '.$url);
        exit;
    }
}


global $DB;

// Charge la PR
$pr = $DB->get_record('subscription_payment_request', ['id' => $pid], '*', MUST_EXIST);

// Langue cible venant de nous (checkout)
$targetLang = optional_param('uilang','', PARAM_ALPHANUMEXT);
$targetLang = strtolower(substr((string)$targetLang, 0, 2));

// Fallbacks si absent
if ($targetLang === '' && !empty($pr->userid)) {
    $ul = $DB->get_field('user', 'lang', ['id' => (int)$pr->userid], IGNORE_MISSING);
    if (!empty($ul)) { $targetLang = strtolower(substr((string)$ul, 0, 2)); }
}
if ($targetLang === '') {
    $targetLang = strtolower(substr((string)($CFG->lang ?? (get_config('defaultuserlang','local_subscriptions') ?? 'ru')), 0, 2));
}
if (!in_array($targetLang, ['fr','en','ru'], true)) { $targetLang = 'ru'; }


// Déduis le provider si non fourni
if ($provider === '') {
    $provider = $pr->payment_provider ?: Provider::defaultProvider();
}
$provider = strtolower($provider);

// CANCEL — commun à tous
if ($result === 'cancel') {
    $params = ['pid' => $pid];
    if ($targetLang !== '') { $params['lang'] = $targetLang; }
    $target = UrlFactory::payment_cancel($params);
    local_subscriptions_redirect_from_return($target, (bool)$embedded);
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
        local_subscriptions_redirect_from_return($target, (bool)$embedded);
    }

    if ($event->type === 'checkout_completed') {
        $params = ['pid' => $pid];
        if (!empty($pr->login_token) && !empty($pr->login_token_expires) && (int)$pr->login_token_expires >= time()) {
            $params['t'] = $pr->login_token;
        }
        if ($targetLang !== '') { $params['lang'] = $targetLang; }
        $target = UrlFactory::payment_success($params);
    } else {
        $meta    = is_array($event->meta ?? null) ? $event->meta : [];
        $params = [
            'pid'  => $pid,
            'code' => (string)($meta['orderStatus'] ?? 'alfa_error'),
            'msg'  => (string)($meta['errorMessage'] ?? $meta['reason'] ?? get_string('alfa_not_paid', 'local_subscriptions')),
        ];
        if ($targetLang !== '') { $params['lang'] = $targetLang; }   // <-- AJOUT
        $target = UrlFactory::payment_error($params);
    }

    local_subscriptions_redirect_from_return($target, (bool)$embedded);
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
    if ($targetLang !== '') { $params['lang'] = $targetLang; } 
    $target = UrlFactory::payment_success($params);

    local_subscriptions_redirect_from_return($target, (bool)$embedded);
}

// Provider inconnu → erreur générique
$params = ['pid' => $pid, 'code' => 'unknown_provider', 'msg' => s($provider)];
if ($targetLang !== '') { $params['lang'] = $targetLang; }   // <-- AJOUT
$target = UrlFactory::payment_error($params);
local_subscriptions_redirect_from_return($target, (bool)$embedded);

