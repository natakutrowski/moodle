<?php
namespace local_subscriptions\payment\alfa;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\dto\{CheckoutInitResult, InternalEvent, ProviderActionResult, ProviderCapabilities};
use local_subscriptions\payment\Provider;
use local_subscriptions\constants\Status;
use local_subscriptions\constants\Operation;
use stdClass;

/**
 * Alfa Bank gateway (MVP one-off).
 * - Crée une session (register.do) et renvoie formUrl.
 * - Parse un webhook RETOUR (optionnel) en revalidant via getOrderStatusExtended.
 *
 * Conventions projet :
 * - DB stocke les montants en "major" (ex: 1990.50). Conversion en minor ICI.
 * - Devise RUB uniquement pour Alfa dans ce MVP.
 */
final class AlfaGateway implements PaymentGatewayInterface {

    /** @var string */
    private $base;
    /** @var string|null */
    private $username;
    /** @var string|null */
    private $password;
    /** @var string|null */
    private $token;
    /** @var string|null */    
    private $webhooksecret;

    public function __construct(array $overrides = []) {
        $cfg = $this->cfg($overrides);

        $this->base          = rtrim((string)$cfg['api_base'], '/');
        $this->username      = $cfg['username'];
        $this->password      = $cfg['password'];
        $this->token         = $cfg['token'];
        $this->webhooksecret = $cfg['webhook_secret'] ?? null; // ajoute la prop si tu veux l'utiliser
    }

    /**
     * Récupère la config Alfa selon l'environnement (test|live),
     * avec fallback sur les anciennes clés si besoin.
     *
     * @param array $overrides  ex: ['api_base' => 'https://override.example', 'token' => '...']
     * @return array{
     *   env:string,
     *   api_base:string,
     *   username:?string,
     *   password:?string,
     *   token:?string,
     *   webhook_secret:?string
     * }
     */
    private function cfg(array $overrides = []) : array {
        $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
        $env = ($env === 'live') ? 'live' : 'test';

        // Nouvelles clés (TEST/LIVE)
        $api = get_config('local_subscriptions', "alfa_{$env}_api_base") ?: '';
        $un  = get_config('local_subscriptions', "alfa_{$env}_username") ?: '';
        $pw  = get_config('local_subscriptions', "alfa_{$env}_password") ?: '';
        $tk  = get_config('local_subscriptions', "alfa_{$env}_token")    ?: '';
        $wh  = get_config('local_subscriptions', "alfa_{$env}_webhook_secret") ?: '';

        $defaults = [
            'env'            => $env,                       // 'test' | 'live' (utile pour logs)
            'api_base'       => rtrim((string)$api, '/'),   // ex: https://alfa.rbsuat.com
            'username'       => $un ?: null,
            'password'       => $pw ?: null,
            'token'          => $tk ?: null,                // en UAT: usage recommandé (param 'token')
            'webhook_secret' => $wh ?: null,
        ];

        return array_replace($defaults, $overrides);
    }


    /**
     * Crée la session de paiement et renvoie l'URL de redirection de la page de paiement Alfa.
     *
     * @param \stdClass $payment_request  Enregistrement DB PR (contient id, currency, price (major), description, etc.)
     * @param array     $options          returnurl, failurl, language ('ru' par défaut), email, phone
     */
    public function create_checkout_session(stdClass $payment_request, array $options = []): CheckoutInitResult {
        global $CFG, $DB;

        $paymentrequesttable = $options['payment_request_table'] ?? 'subscription_payment_request';

        if (empty($this->base)) {
            throw new \moodle_exception('alfa_missing_api_base', 'local_subscriptions');
        }

        $currency = strtoupper($payment_request->currency ?? '');
        if ($currency !== 'RUB') {
            throw new \moodle_exception('alfa_rub_only', 'local_subscriptions');
        }

        // (OPTION) Vérifier la cohérence PR.price vs prix configuré RUB
        $cfgRub = null;
        if (!empty($payment_request->planid)) {
            $cfgRub = $DB->get_field('subscription_plan_price', 'price',
                ['planid' => (int)$payment_request->planid, 'currency' => 'RUB'], IGNORE_MISSING);
        }
        // --- Opération courante (depuis options ou PR)
        $op = $options['operation'] ?? ($payment_request->operation ?? '');

        // --- Prix catalogue RUB (si dispo)
        $cfgRub = null;
        if (!empty($payment_request->planid)) {
            $cfgRub = $DB->get_field('subscription_plan_price', 'price',
                ['planid' => (int)$payment_request->planid, 'currency' => 'RUB'], IGNORE_MISSING);
        }

        // --- Politique de cohérence prix
        $prPrice = (float)$payment_request->price;

        if ($op === Operation::PURCHASE_NEW || $op === Operation::QUEUE_FUTURE) {
            // Achat standard → prix PR doit correspondre exactement au catalogue
            if ($cfgRub !== null && abs($prPrice - (float)$cfgRub) > 0.01) {
                throw new \moodle_exception('alfa_price_mismatch', 'local_subscriptions', '',
                    'PR='.$prPrice.' / CFG='.$cfgRub);
            }
        } else {
            // UPGRADE  → on laisse passer le prix Advisor
            // Garde minimale: non-négatif
            if ($prPrice <= 0) {
                throw new \moodle_exception('alfa_price_mismatch', 'local_subscriptions', '',
                    'non-positive: PR='.$prPrice.(($cfgRub!==null)?' / CFG='.$cfgRub:''));
            }
            // (Optionnel) journaliser si > catalogue (suspicious), sans bloquer
            if ($cfgRub !== null && $prPrice > (float)$cfgRub * 1.25) {
                error_log('[alfa][price_guard] upgrade/queue price unusually high: PR='.$prPrice.' CFG='.$cfgRub);
            }
        }

        // --- orderNumber unique -------------------------------------------
        $attempt = (int)($payment_request->attempts ?? 0) + 1;
        $DB->update_record($paymentrequesttable, (object)[
            'id' => $payment_request->id,
            'attempts' => $attempt,
            'last_attempt' => time(),
            'payment_provider' => Provider::ALFA,
            'status' => Status::PENDING,
        ]);
        $prefix = $options['order_number_prefix'] ?? 'sub';

        $orderNumber = $prefix . '-' . $payment_request->id . '-' . $attempt;

        // Conversion via LOCK (kopecks).
        if (!isset($payment_request->locked_final_price) || (float)$payment_request->locked_final_price <= 0) {
            throw new \moodle_exception('paylock_missing_lockdata', 'local_subscriptions');
        }

        $lockedList     = (float)($payment_request->locked_list_price      ?? 0.0);
        $lockedPct      = (int)  ($payment_request->locked_discount_percent ?? 0);
        $lockedAmount   = (float)($payment_request->locked_discount_amount  ?? 0.0);
        $lockedReason   =        ($payment_request->locked_discount_reason  ?? null);
        $lockedFinal    = (float) $payment_request->locked_final_price;

        // Si create_session a déjà mis amount_minor, on l’utilise tel quel (source de vérité)
        $amountMinor = isset($payment_request->amount_minor)
            ? (int)$payment_request->amount_minor
            : $this->major_to_minor($lockedFinal);

        if ($amountMinor <= 0) {
            throw new \moodle_exception('alfa_nonpositive_amount', 'local_subscriptions');
        }


        // URLs de retour (peuvent être passées via $options).
        $returnUrl = $options['returnurl'] ?? ($CFG->wwwroot . '/local/subscriptions/payment/alfa_return.php?pid=' . $payment_request->id);
        // Important : on met fail=1 pour router vers payment_cancel.php
        $failUrl   = $options['failurl']   ?? ($returnUrl . '&fail=1');

        $planname = '';
        if (!empty($payment_request->planid)) {
            $planname = (string)($DB->get_field('subscription_plan', 'name', ['id' => (int)$payment_request->planid], IGNORE_MISSING) ?? '');
        }

        $desc = 'CampusFR — '.($planname ?: get_string('alfa:productname', 'local_subscriptions', 'Abonnement'))
            .' — '.number_format((float)$payment_request->locked_final_price, 2, '.', '').' '.$payment_request->currency;

        $payload = [
            'orderNumber' => $orderNumber,
            'amount'      => $amountMinor,
            'returnUrl'   => $returnUrl,
            'failUrl'     => $failUrl,
            'description' => $desc,
            'language'    => $options['language'] ?? 'ru',
        ];

        // Infos client : encodage déjà RFC3986 dans post() → OK
        $customer = [];
        if (!empty($payment_request->userid))   { $customer['clientId']  = (string)$payment_request->userid; }
        if (!empty($options['email'] ?? $payment_request->email ?? null)) {
            $customer['email'] = $options['email'] ?? $payment_request->email;
        }
        if (!empty($options['firstname'] ?? $payment_request->firstname ?? null)) {
            $customer['firstName'] = $options['firstname'] ?? $payment_request->firstname;
        }
        if (!empty($options['lastname'] ?? $payment_request->lastname ?? null)) {
            $customer['lastName']  = $options['lastname'] ?? $payment_request->lastname;
        }
        $meta = [
            'pr_id'                   => (string)$payment_request->id,
            'locked_list_price'       => (string)$lockedList,
            'locked_discount_percent' => (string)$lockedPct,
            'locked_discount_amount'  => (string)$lockedAmount,
            'locked_discount_reason'  => (string)($lockedReason ?? ''),
            'locked_final_price'      => (string)$lockedFinal,
            'locked_currency'         => (string)$payment_request->currency,
        ];
        if ($customer) { $customer['meta'] = $meta; } else { $customer = ['meta' => $meta]; }
        $payload['jsonParams'] = json_encode($customer, JSON_UNESCAPED_UNICODE);

        // ---- Appel unique : mode token → PAS de currency (le gagnant) --------------
        $response = $this->post('/payment/rest/register.do', $payload);

        if (!empty($response['errorCode']) && $response['errorCode'] !== '0') {
            $msg = 'Alfa register.do error '.$response['errorCode'].' : '.($response['errorMessage'] ?? $response['actionCodeDescription'] ?? 'unknown');
            $DB->update_record($paymentrequesttable, (object)[
                'id'            => $payment_request->id,
                'response_json' => json_encode(['register' => $response, 'payload' => $payload], JSON_UNESCAPED_UNICODE),
                'last_error'    => $msg,
            ]);
            throw new \moodle_exception('alfa_register_error', 'local_subscriptions', '', $msg);
        }

        $formUrl = $response['formUrl'] ?? null;
        $orderId = $response['orderId'] ?? null;
        if (!$formUrl || !$orderId) {
            $raw = json_encode(['register' => $response, 'payload' => $payload], JSON_UNESCAPED_UNICODE);
            $DB->update_record($paymentrequesttable, (object)[
                'id'            => $payment_request->id,
                'response_json' => $raw,
                'last_error'    => 'Missing formUrl/orderId from Alfa',
            ]);
            throw new \moodle_exception('alfa_missing_formurl', 'local_subscriptions');
        }

        // Persist : orderId & formUrl
        $DB->update_record($paymentrequesttable, (object)[
            'id'               => $payment_request->id,
            'sessionid'        => $orderId,         // Alfa orderId
            'payment_link'     => $formUrl,
            'response_json'    => json_encode(['register' => $response, 'payload' => $payload], JSON_UNESCAPED_UNICODE),
            'status'           => Status::PENDING,
        ]);

        return new CheckoutInitResult($formUrl);
    }

    /**
     * Parse un webhook RETOUR/notification.
     * Pour fiabiliser : revalide systématiquement le statut via getOrderStatusExtended.
     */
    public function parse_webhook(string $payload, array $headers): InternalEvent {
        global $DB;

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            parse_str($payload, $data);
        }
        $paymentcontext = $data['payment_context'] ?? null;
        $paymentrequesttable = $data['payment_request_table'] ?? 'subscription_payment_request';
        $orderNumber = $data['orderNumber'] ?? null;
        $orderId     = $data['orderId']     ?? null;

        if (!$orderNumber && !$orderId) {
            // On émet un échec "technique" minimal (aucun identifiant exploitable).
            return new InternalEvent('payment_failed', [
                'payment_request_id' => null,
                'currency' => 'RUB',
                'amount_minor' => null,
                'meta' => ['reason' => 'alfa_webhook_missing_ids', 'raw' => $data],
            ]);
        }

        $status = $this->get_status($orderNumber, $orderId);

        // On peut enrichir le PR (journalisation « last status »)
        if ($orderNumber) {
            $parts = explode('-', (string)$orderNumber);

            // Formats supportés :
            // sub-123-1 / digital-123-1 => id = parts[1]
            // 123-1 => id = parts[0]
            // 123 => id = parts[0]
            $prid = isset($parts[1]) && !is_numeric($parts[0])
                ? (int)$parts[1]
                : (int)$parts[0];

            if ($prid > 0) {
                $DB->set_field(
                    $paymentrequesttable,
                    'response_json',
                    json_encode(['last_status' => $status], JSON_UNESCAPED_UNICODE),
                    ['id' => $prid]
                );
            }
        }

        return $this->map_status_to_event($status, $orderNumber, $orderId, $paymentcontext, $paymentrequesttable);
    }

    // ========== Internals ==========

    private function major_to_minor($major): int {
        $value = (string)$major;
        if (function_exists('bcmul')) {
            return (int) bcmul($value, '100', 0);
        }
        return (int) round(((float)$major) * 100);
    }

    private function post(string $path, array $fields): array {
        $url = $this->base . $path; // ex: https://alfa.rbsuat.com/payment/rest/...
        $ch = curl_init($url);

        // Auth: priorité au token (paramètre 'token'); sinon userName/password.
        $payload = $fields;
        if (!empty($this->token)) {
            $payload = array_merge(['token' => $this->token], $payload);
        } else {
            $auth = [];
            if ($this->username !== null) { $auth['userName'] = $this->username; }
            if ($this->password !== null) { $auth['password'] = $this->password; }
            $payload = array_merge($auth, $payload);
        }


        // Encodage strict RFC3986 (évite pertes de champs côté passerelle)
        $encoded = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $encoded,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', 'CURL: ' . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            parse_str($raw, $jsonArr);
            if (is_array($jsonArr) && !empty($jsonArr)) {
                return $jsonArr;
            }
            throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', "HTTP $code: $raw");
        }
        return $json;
    }


    private function get_status(?string $orderNumber, ?string $orderId): array {
        // En mode token, Alfa exige UNIQUEMENT orderId.
        if (!empty($this->token) && !empty($orderId)) {
            return $this->post('/payment/rest/getOrderStatusExtended.do', [
                'orderId' => $orderId,
            ]);
        }

        // Sinon, on fait au mieux avec ce qu'on a.
        if (!empty($orderId)) {
            // user/pass : orderId suffit déjà
            return $this->post('/payment/rest/getOrderStatusExtended.do', [
                'orderId' => $orderId,
            ]);
        }

        // En dernier recours : orderNumber seul.
        if (!empty($orderNumber)) {
            return $this->post('/payment/rest/getOrderStatusExtended.do', [
                'orderNumber' => $orderNumber,
            ]);
        }

        // Rien à interroger : renvoyer une "erreur" structurée
        return ['errorCode' => '4', 'errorMessage' => 'orderId/orderNumber missing'];
    }


    private function map_status_to_event(
        array $status,
        ?string $orderNumber,
        ?string $orderId,
        ?string $paymentcontext = null,
        string $paymentrequesttable = 'subscription_payment_request'
    ): InternalEvent {
        global $DB;

        $orderStatus = isset($status['orderStatus']) ? (int)$status['orderStatus'] : null;
        $amountMinor = isset($status['amount']) ? (int)$status['amount'] : null;

        // Résoudre l'ID du Payment Request
        $prid = null;

        if (!empty($orderNumber)) {
            $parts = explode('-', (string)$orderNumber);

            // Formats supportés :
            // sub-123-1 / digital-123-1 => id = parts[1]
            // 123-1 => id = parts[0]
            // 123 => id = parts[0]
            if (isset($parts[1]) && !is_numeric($parts[0])) {
                $prid = (string)(int)$parts[1];
            } else {
                $prid = (string)(int)$parts[0];
            }
        }

        if ((!$prid || $prid === '0') && !empty($orderId)) {
            $row = $DB->get_record($paymentrequesttable, ['sessionid' => $orderId], 'id', IGNORE_MISSING);
            if ($row) {
                $prid = (string)$row->id;
            }
        }

        $base = [
            'payment_request_id' => $prid,
            'currency'           => 'RUB', // ta logique métier utilise la monnaie de la PR
            'amount_minor'       => $amountMinor,
            'meta'               => [
                'provider'          => Provider::ALFA,
                'payment_context'   => $paymentcontext,
                'payment_request_table' => $paymentrequesttable,
                'session'           => $orderId, // <-- filet de secours attendu par PaymentService
                'orderId'           => $orderId,
                'orderStatus'       => $orderStatus,
                'provider_currency' => $status['currency'] ?? null, // souvent "810" en UAT
                'errorMessage'      => $status['actionCodeDescription'] ?? ($status['errorMessage'] ?? null),
                'raw'               => $status,
            ],
        ];

        if ($orderStatus === 2) {
            return new InternalEvent('checkout_completed', $base);
        }
        if ($orderStatus === 0) {
            $base['meta']['reason'] = 'registered_not_paid';
            return new InternalEvent('payment_failed', $base);
        }
        if ($orderStatus === 6) {
            $base['meta']['reason'] = 'authorization_declined';
            return new InternalEvent('payment_failed', $base);
        }
        $base['meta']['reason'] = 'not_paid';
        return new InternalEvent('payment_failed', $base);
    }


    public function cancel_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function resume_subscription(string $provider_subscription_id, array $opts = []): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function upgrade_subscription(string $provider_subscription_id, array $opts): ProviderActionResult {
        return new ProviderActionResult(false, 'Not implemented for Alfa yet');
    }

    public function get_customer_portal_url(?string $provider_customer_id, array $opts = []): ?string {
        return null;
    }

    public function capabilities(): ProviderCapabilities {
        $c = new ProviderCapabilities();
        $c->supports_recurring = false;
        $c->supports_portal = false;
        $c->currencies = ['RUB'];
        return $c;
    }
}
