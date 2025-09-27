<?php
namespace local_subscriptions\payment\alfa;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\PortalGatewayInterface;
use local_subscriptions\payment\dto\{CheckoutInitResult, InternalEvent, ProviderActionResult, ProviderCapabilities};
use local_subscriptions\url\UrlFactory;
use local_subscriptions\payment\Provider;
use local_subscriptions\constants\Status;
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

    public function __construct() {
        $this->base     = rtrim(get_config('local_subscriptions', 'alfa_api_base') ?: '', '/');
        $this->username = get_config('local_subscriptions', 'alfa_username') ?: null;
        $this->password = get_config('local_subscriptions', 'alfa_password') ?: null;
        $this->token    = get_config('local_subscriptions', 'alfa_token') ?: null;
    }

    /**
     * Crée la session de paiement et renvoie l'URL de redirection de la page de paiement Alfa.
     *
     * @param \stdClass $payment_request  Enregistrement DB PR (contient id, currency, price (major), description, etc.)
     * @param array     $options          returnurl, failurl, language ('ru' par défaut), email, phone
     */
    public function create_checkout_session(stdClass $payment_request, array $options = []): CheckoutInitResult {
        global $CFG, $DB;

        if (empty($this->base)) {
            throw new \moodle_exception('configmissing', 'local_subscriptions', '', 'alfa_api_base');
        }

        $currency = strtoupper($payment_request->currency ?? '');
        if ($currency !== 'RUB') {
            throw new \moodle_exception('invalidrequest', 'local_subscriptions', '', 'Alfa est configuré pour RUB uniquement.');
        }

        // --- orderNumber unique -------------------------------------------
        $attempt = (int)($payment_request->attempts ?? 0) + 1;
        $DB->update_record('subscription_payment_request', (object)[
            'id' => $payment_request->id,
            'attempts' => $attempt,
            'last_attempt' => time(),
            'payment_provider' => 'alfa',
            'status' => 'pending',
        ]);
        $orderNumber = $payment_request->id . '-' . $attempt;

        // Conversion major -> minor (kopecks).
        $amountMinor = $this->major_to_minor($payment_request->price);
        // URLs de retour (peuvent être passées via $options).
        $returnUrl = $options['returnurl'] ?? ($CFG->wwwroot . '/local/subscriptions/payment/alfa_return.php?prid=' . $payment_request->id);
        // Important : on met fail=1 pour router vers payment_cancel.php
        $failUrl   = $options['failurl']   ?? ($CFG->wwwroot . '/local/subscriptions/payment/alfa_return.php?prid=' . $payment_request->id . '&fail=1');

        $payload = [
            'orderNumber' => $orderNumber,
            'amount'      => $amountMinor,
            'currency'    => 643, // RUB
            'returnUrl'   => $returnUrl,
            'failUrl'     => $failUrl,
            'description' => $payment_request->description ?? 'Subscription payment',
            'language'    => $options['language'] ?? 'ru', // 'ru' ou 'en'
            // Infos client utiles (anti-fraude / UI banque)
            'jsonParams'  => json_encode([
                'email' => $options['email'] ?? $payment_request->email ?? null,
                'phone' => $options['phone'] ?? null,
            ]),
        ];

        $response = $this->post('/payment/rest/register.do', $payload);

        if (!empty($response['errorCode']) && $response['errorCode'] !== '0') {
            $msg = 'Alfa register.do error '.$response['errorCode'].' : '.($response['errorMessage'] ?? $response['actionCodeDescription'] ?? 'unknown');
            // Journalise pour debug
            $DB->update_record('subscription_payment_request', (object)[
                'id'            => $payment_request->id,
                'response_json' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'last_error'    => $msg,
            ]);
            throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', $msg);
        }

        $formUrl = $response['formUrl'] ?? null;
        $orderId = $response['orderId'] ?? null;
        if (!$formUrl || !$orderId) {
            $raw = json_encode($response, JSON_UNESCAPED_UNICODE);
            $DB->update_record('subscription_payment_request', (object)[
                'id'            => $payment_request->id,
                'payment_provider' => Provider::ALFA,
                'status'        => Status::PENDING,
                'response_json' => $raw,
                'last_error'    => 'Missing formUrl/orderId from Alfa',
            ]);
            throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', 'Missing formUrl/orderId from Alfa');
        }

        // Persist formUrl + orderId dans ta table PR
        $DB->update_record('subscription_payment_request', (object)[
            'id'                => $payment_request->id,
            'payment_provider'  => Provider::ALFA,
            'sessionid'         => $orderId, // identifiant Alfa
            'payment_link'      => $formUrl,
            'response_json'     => json_encode(['register' => $response, 'payload' => $payload], JSON_UNESCAPED_UNICODE),
            'status'            => Status::PENDING,
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
            $DB->set_field('subscription_payment_request', 'response_json', json_encode(['last_status' => $status], JSON_UNESCAPED_UNICODE), ['id' => (int)$orderNumber]);
        }

        return $this->map_status_to_event($status, $orderNumber, $orderId);
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

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
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
        $fields = [];
        if ($orderId)     { $fields['orderId'] = $orderId; }
        if ($orderNumber) { $fields['orderNumber'] = $orderNumber; }
        return $this->post('/payment/rest/getOrderStatusExtended.do', $fields);
    }

    private function map_status_to_event(array $status, ?string $orderNumber, ?string $orderId): InternalEvent {
        $orderStatus = isset($status['orderStatus']) ? (int)$status['orderStatus'] : null;
        $amountMinor = isset($status['amount']) ? (int)$status['amount'] : null; // Alfa renvoie souvent le montant en minor
        
        $base = [
            'payment_request_id' => $orderNumber ? (string)$orderNumber : null,
            'currency'           => 'RUB',
            'amount_minor'       => $amountMinor,
            'meta'               => [
                'provider'    => Provider::ALFA,
                'orderId'     => $orderId ?? ($status['orderId'] ?? null),
                'orderStatus' => $orderStatus,
                'errorMessage'=> $status['actionCodeDescription'] ?? ($status['errorMessage'] ?? null),
                'raw'         => $status,
            ],
        ];

        // Mapping minimal viable :
        // 2 = payé, 0 = enregistré (non payé), 6 = refusé
        if ($orderStatus === 2) {
            // Payé : on peut copier orderId en transactionid côté DB si tu veux (dans PaymentService).
            return new InternalEvent('checkout_completed', $base);
        }
        if ($orderStatus === 0) {
            return new InternalEvent('payment_failed', $base + ['meta' => $base['meta'] + ['reason' => 'registered_not_paid']]);
        }
        if ($orderStatus === 6) {
            return new InternalEvent('payment_failed', $base + ['meta' => $base['meta'] + ['reason' => 'authorization_declined']]);
        }
        // 1(preauth),3(cancel),4(refund),5(3DS pending) -> pour l’UI on renvoie payment_error.
        return new InternalEvent('payment_failed', $base + ['meta' => $base['meta'] + ['reason' => 'not_paid']]);
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
