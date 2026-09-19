<?php
namespace local_subscriptions\payment\alfa;

use local_subscriptions\payment\PaymentGatewayInterface;
use local_subscriptions\payment\RefundGatewayInterface;
use local_subscriptions\payment\RefundHistoryGatewayInterface;
use local_subscriptions\payment\dto\ProviderRefundResult;
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
final class AlfaGateway implements PaymentGatewayInterface, RefundGatewayInterface, RefundHistoryGatewayInterface, AlfaFastPaymentGatewayInterface {

    /** @var string */
    private $base;
    /** @var string|null */
    private $username;
    /** @var string|null */
    private $password;
    /** @var string|null */
    private $token;
    /** @var string|null */
    private $refundusername;
    /** @var string|null */
    private $refundpassword;
    /** @var string|null */    
    private $webhooksecret;

    public function __construct(array $overrides = []) {
        $cfg = $this->cfg($overrides);

        $this->base          = rtrim((string)$cfg['api_base'], '/');
        $this->username      = $cfg['username'];
        $this->password      = $cfg['password'];
        $this->token         = $cfg['token'];
        $this->refundusername = $cfg['refund_username'];
        $this->refundpassword = $cfg['refund_password'];
        $this->webhooksecret = $cfg['webhook_secret'] ?? null;
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
     *   refund_username:?string,
     *   refund_password:?string,
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
        $run = get_config(
            'local_subscriptions',
            "alfa_{$env}_refund_username"
        ) ?: '';
        $rpw = get_config(
            'local_subscriptions',
            "alfa_{$env}_refund_password"
        ) ?: '';
        $wh  = get_config('local_subscriptions', "alfa_{$env}_webhook_secret") ?: '';

        $defaults = [
            'env'            => $env,
            'api_base'       => rtrim((string)$api, '/'),
            'username'       => $un ?: null,
            'password'       => $pw ?: null,
            'token'          => $tk ?: null,
            'refund_username' => $run !== '' ? $run : ($un ?: null),
            'refund_password' => $rpw !== '' ? $rpw : ($pw ?: null),
            'webhook_secret' => $wh ?: null,
        ];

        return array_replace($defaults, $overrides);
    }


    /**
     * Crée la session de paiement et renvoie l'URL de redirection de la page de paiement Alfa.
     *
     * @param stdClass $payment_request  Enregistrement DB PR (contient id, currency, price (major), description, etc.)
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

        return new CheckoutInitResult(
            $formUrl,
            (string)$orderId
        );
    }

    /**
     * Returns the dedicated Alfa REST API username.
     *
     * The existing Moodle settings are historically named "refund_*", but
     * they contain the merchant -api credentials used by refund.do and by
     * fast-payment APIs such as Alfa Pay / SBP.
     */
    private function api_username(): ?string {
        return $this->refundusername;
    }

    /**
     * Returns the dedicated Alfa REST API password.
     *
     * @see self::api_username()
     */
    private function api_password(): ?string {
        return $this->refundpassword;
    }

    /**
     * Registers and initiates a dedicated Alfa Pay payment.
     *
     * Alfa documents /alfapay/payment.do as the merchant-side entry point for
     * Alfa Pay. Unlike register.do, this endpoint both creates the order and
     * returns the redirect that starts Alfa Pay authorization.
     */
    public function create_alfapay_session(
        stdClass $payment_request,
        array $options = []
    ): CheckoutInitResult {
        global $DB;

        $paymentrequesttable =
            $options['payment_request_table']
            ?? 'subscription_payment_request';

        $currency = strtoupper(
            trim((string)($payment_request->currency ?? ''))
        );
        if ($currency !== 'RUB') {
            throw new \moodle_exception(
                'alfa_rub_only',
                'local_subscriptions'
            );
        }

        if (
            $this->api_username() === null
            || trim((string)$this->api_username()) === ''
            || $this->api_password() === null
            || trim((string)$this->api_password()) === ''
        ) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Alfa Pay requires the merchant -api username/password pair.'
            );
        }

        $attempt = (int)($payment_request->attempts ?? 0) + 1;
        $DB->update_record(
            $paymentrequesttable,
            (object)[
                'id' => $payment_request->id,
                'attempts' => $attempt,
                'last_attempt' => time(),
                'payment_provider' => Provider::ALFA,
                'status' => Status::PENDING,
            ]
        );

        $prefix = trim(
            (string)($options['order_number_prefix'] ?? 'sub')
        );
        if ($prefix === '') {
            $prefix = 'sub';
        }

        $orderNumber = $prefix
            . '-'
            . (int)$payment_request->id
            . '-'
            . $attempt;

        $amountMinor = isset($payment_request->amount_minor)
            ? (int)$payment_request->amount_minor
            : $this->major_to_minor(
                (float)($payment_request->locked_final_price ?? 0)
            );
        if ($amountMinor <= 0) {
            throw new \moodle_exception(
                'alfa_nonpositive_amount',
                'local_subscriptions'
            );
        }

        $returnUrl = trim(
            (string)($options['returnurl'] ?? '')
        );
        $failUrl = trim(
            (string)($options['failurl'] ?? '')
        );
        if ($returnUrl === '' || $failUrl === '') {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Alfa Pay requires returnUrl and failUrl.'
            );
        }

        $language = strtolower(
            trim((string)($options['language'] ?? 'ru'))
        );
        // Alfa Pay REST expects the ISO A2 value in the bank's canonical
        // uppercase form (RU / EN). The Payment Page widget uses lowercase,
        // but this dedicated API is a different contract.
        $language = str_starts_with($language, 'ru')
            ? 'RU'
            : 'EN';

        $description = trim(
            (string)(
                $options['description']
                ?? $payment_request->description
                ?? 'CampusFR'
            )
        );
        if ($description === '') {
            $description = 'CampusFR';
        }

        $payload = [
            'orderNumber' => $orderNumber,
            'amount' => $amountMinor,
            'currencyCode' => 643,
            'returnUrl' => $returnUrl,
            'failUrl' => $failUrl,
            'description' => \core_text::substr(
                $description,
                0,
                598
            ),
            'language' => $language,
        ];

        if (!empty($options['ip'])) {
            $payload['ip'] = trim((string)$options['ip']);
        }

        $response = $this->post_json(
            $this->alfapay_endpoint(),
            $payload,
            true
        );

        $success = !empty($response['success']);
        $data = is_array($response['data'] ?? null)
            ? $response['data']
            : [];
        $error = is_array($response['error'] ?? null)
            ? $response['error']
            : [];

        $orderId = trim((string)($data['orderId'] ?? ''));
        $redirect = trim((string)($data['redirect'] ?? ''));

        if (!$success || $orderId === '' || $redirect === '') {
            $message = trim(
                (string)(
                    $error['message']
                    ?? 'Alfa Pay did not return an executable payment redirect.'
                )
            );

            // Server-side diagnostic only. Never log credentials or the full
            // request payload. This stays out of the browser/AJAX response.
            error_log(
                '[CampusFR][AlfaPay] initialization rejected: '
                . json_encode(
                    [
                        'success' => $success,
                        'message' => $message,
                        'orderNumber' => $orderNumber,
                        'amount' => $amountMinor,
                        'currencyCode' => 643,
                        'language' => $language,
                        'endpointHost' => (string)(parse_url(
                            $this->alfapay_endpoint(),
                            PHP_URL_HOST
                        ) ?: ''),
                        'apiLoginLooksLikeApi' => str_ends_with(
                            strtolower(trim((string)$this->api_username())),
                            '-api'
                        ),
                        'hasOrderId' => $orderId !== '',
                        'hasRedirect' => $redirect !== '',
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );

            $DB->update_record(
                $paymentrequesttable,
                (object)[
                    'id' => (int)$payment_request->id,
                    'response_json' => json_encode(
                        ['alfapay' => $response, 'payload' => $payload],
                        JSON_UNESCAPED_UNICODE
                    ),
                    'last_error' => $message,
                ]
            );
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                $message
            );
        }

        $DB->update_record(
            $paymentrequesttable,
            (object)[
                'id' => (int)$payment_request->id,
                'sessionid' => $orderId,
                'payment_link' => $redirect,
                'response_json' => json_encode(
                    ['alfapay' => $response, 'payload' => $payload],
                    JSON_UNESCAPED_UNICODE
                ),
                'status' => Status::PENDING,
            ]
        );

        return new CheckoutInitResult(
            $redirect,
            $orderId
        );
    }

    private function alfapay_endpoint(): string {
        $env = get_config(
            'local_subscriptions',
            'alfa_env'
        ) === 'live' ? 'live' : 'test';

        if ($env !== 'live') {
            return 'https://alfa.rbsuat.com/alfapay/payment.do';
        }

        $apiusername = strtolower(trim((string)$this->api_username()));

        // Alfa documents two production hosts for Alfa Pay:
        // - API logins with the "r-" prefix use payment.alfabank.ru;
        // - API logins without the prefix use pay.alfabank.ru.
        return str_starts_with($apiusername, 'r-')
            ? 'https://payment.alfabank.ru/alfapay/payment.do'
            : 'https://pay.alfabank.ru/alfapay/payment.do';
    }

    /**
     * Registers an Alfa order and creates a dynamic SBP C2B QR code.
     *
     * The order registration intentionally reuses the certified register.do
     * path. SBP then uses the dedicated merchant API account for QR creation.
     */
    public function create_sbp_session(
        stdClass $payment_request,
        array $options = []
    ): AlfaSbpInitResult {
        global $DB;

        if (
            $this->api_username() === null
            || trim((string)$this->api_username()) === ''
            || $this->api_password() === null
            || trim((string)$this->api_password()) === ''
        ) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'SBP requires the Alfa merchant API username/password pair.'
            );
        }

        $paymentrequesttable =
            $options['payment_request_table']
            ?? 'subscription_payment_request';

        // register.do persists the Alfa orderId in the legacy payment request.
        $registered = $this->create_checkout_session(
            $payment_request,
            $options
        );

        $orderid = trim(
            (string)($registered->provider_session_id ?? '')
        );
        if ($orderid === '') {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Alfa did not return an order id before SBP QR creation.'
            );
        }

        $qrsize = (int)($options['sbp_qr_size'] ?? 360);
        $qrsize = max(160, min(720, $qrsize));

        $response = $this->post(
            '/payment/rest/sbp/c2b/qr/dynamic/get.do',
            [
                'mdOrder' => $orderid,
                'qrHeight' => (string)$qrsize,
                'qrWidth' => (string)$qrsize,
                'qrFormat' => 'image',
            ],
            'api_userpass'
        );

        $errorcode = trim((string)($response['errorCode'] ?? ''));
        if ($errorcode !== '' && $errorcode !== '0') {
            $message = trim(
                (string)(
                    $response['errorMessage']
                    ?? ('Alfa SBP QR error ' . $errorcode)
                )
            );

            error_log(
                '[CampusFR][SBP] QR initialization rejected: '
                . json_encode(
                    [
                        'errorCode' => $errorcode,
                        'message' => $message,
                        'orderIdPresent' => true,
                        'qrStatus' => (string)($response['qrStatus'] ?? ''),
                        'apiLoginLooksLikeApi' => str_ends_with(
                            strtolower(trim((string)$this->api_username())),
                            '-api'
                        ),
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );

            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                $message
            );
        }

        $qrid = trim((string)($response['qrId'] ?? ''));
        $qrstatus = strtoupper(trim((string)($response['qrStatus'] ?? '')));
        $payload = trim((string)($response['payload'] ?? ''));
        $renderedqr = trim((string)($response['renderedQr'] ?? ''));

        if (
            $qrid === ''
            || $payload === ''
            || $qrstatus !== 'STARTED'
        ) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Alfa SBP did not return an active dynamic QR code.'
            );
        }

        $existing = $DB->get_field(
            $paymentrequesttable,
            'response_json',
            ['id' => (int)$payment_request->id],
            IGNORE_MISSING
        );
        $journal = json_decode((string)$existing, true);
        if (!is_array($journal)) {
            $journal = [];
        }
        $journal['sbp'] = [
            'qrId' => $qrid,
            'qrStatus' => $qrstatus,
            'payload' => $payload,
            // Do not persist the bulky rendered QR image in the legacy journal.
            'renderedQrPresent' => $renderedqr !== '',
        ];

        $DB->update_record(
            $paymentrequesttable,
            (object)[
                'id' => (int)$payment_request->id,
                'sessionid' => $orderid,
                'response_json' => json_encode(
                    $journal,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'status' => Status::PENDING,
            ]
        );

        return new AlfaSbpInitResult(
            $orderid,
            $qrid,
            $qrstatus,
            $payload,
            $renderedqr !== '' ? $renderedqr : null
        );
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

    private function post(
        string $path,
        array $fields,
        string $authmode = 'auto'
    ): array {
        return $this->post_url(
            $this->base . $path,
            $fields,
            $authmode
        );
    }

    /**
     * Sends JSON to a dedicated Alfa API endpoint.
     *
     * Alfa Pay /alfapay/payment.do explicitly requires
     * Content-Type: application/json.
     */
    private function post_json(
        string $url,
        array $fields,
        bool $useapicredentials = false
    ): array {
        $payload = $fields;

        if ($useapicredentials) {
            $apiusername = $this->api_username();
            $apipassword = $this->api_password();

            if (
                $apiusername === null
                || trim((string)$apiusername) === ''
                || $apipassword === null
                || trim((string)$apipassword) === ''
            ) {
                throw new \moodle_exception(
                    'paymentgatewayerror',
                    'local_subscriptions',
                    '',
                    'Alfa REST API credentials are missing.'
                );
            }

            $payload = array_merge(
                [
                    'userName' => $apiusername,
                    'password' => $apipassword,
                ],
                $payload
            );
        }

        $jsonpayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($jsonpayload === false) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'Could not encode Alfa JSON request.'
            );
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $jsonpayload,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                'CURL: ' . $err
            );
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                "HTTP $code: $raw"
            );
        }

        return $json;
    }

    private function post_url(
        string $url,
        array $fields,
        string $authmode = 'auto'
    ): array {
        $ch = curl_init($url);

        // Authentication policy:
        // - auto: preserve historical token-first behaviour;
        // - userpass: explicitly use the merchant -api credentials.
        //
        // Alfa refund.do requires userName/password on the current REST
        // merchant API even when token authentication is used by other
        // payment operations.
        $payload = $fields;

        if ($authmode === 'merchant_userpass') {
            $authusername = $this->username;
            $authpassword = $this->password;

            if (
                $authusername === null
                || trim((string)$authusername) === ''
                || $authpassword === null
                || trim((string)$authpassword) === ''
            ) {
                throw new \moodle_exception(
                    'paymentgatewayerror',
                    'local_subscriptions',
                    '',
                    'Alfa merchant API credentials are missing.'
                );
            }

            $payload = array_merge(
                [
                    'userName' => $authusername,
                    'password' => $authpassword,
                ],
                $payload
            );
        } else if ($authmode === 'api_userpass') {
            $authusername = $this->api_username();
            $authpassword = $this->api_password();

            if (
                $authusername === null
                || trim((string)$authusername) === ''
                || $authpassword === null
                || trim((string)$authpassword) === ''
            ) {
                throw new \moodle_exception(
                    'paymentgatewayerror',
                    'local_subscriptions',
                    '',
                    'Alfa REST API credentials are missing.'
                );
            }

            $payload = array_merge(
                [
                    'userName' => $authusername,
                    'password' => $authpassword,
                ],
                $payload
            );
        } else if ($authmode === 'userpass') {
            $authusername = $this->api_username();
            $authpassword = $this->api_password();

            if (
                $authusername === null
                || trim((string)$authusername) === ''
                || $authpassword === null
                || trim((string)$authpassword) === ''
            ) {
                throw new \moodle_exception(
                    'alfa_refund_credentials_missing',
                    'local_subscriptions'
                );
            }

            $payload = array_merge(
                [
                    'userName' => $authusername,
                    'password' => $authpassword,
                ],
                $payload
            );
        } else if (!empty($this->token)) {
            $payload = array_merge(
                ['token' => $this->token],
                $payload
            );
        } else {
            $auth = [];
            if ($this->username !== null) {
                $auth['userName'] = $this->username;
            }
            if ($this->password !== null) {
                $auth['password'] = $this->password;
            }
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


    public function refund_payment(
        string $providerpaymentid,
        int $amountminor,
        string $currency,
        array $options = []
    ): ProviderRefundResult {
        $providerpaymentid = trim($providerpaymentid);
        $currency = strtoupper(trim($currency));

        if ($providerpaymentid === '') {
            throw new \coding_exception('Alfa refund requires an order identifier.');
        }
        if ($currency !== 'RUB') {
            throw new \coding_exception('The current Alfa gateway refunds RUB payments only.');
        }
        if ($amountminor <= 0) {
            throw new \coding_exception('Alfa refund amount must be positive.');
        }

        $response = $this->post(
            '/payment/rest/refund.do',
            [
                'orderId' => $providerpaymentid,
                'amount' => $amountminor,
            ],
            'userpass'
        );

        $errorcode = trim((string)($response['errorCode'] ?? ''));
        if ($errorcode !== '' && $errorcode !== '0') {
            if ($errorcode === '5') {
                throw new \moodle_exception(
                    'alfa_refund_access_denied',
                    'local_subscriptions',
                    '',
                    $this->refundusername ?? ''
                );
            }

            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                (string)(
                    $response['errorMessage']
                    ?? ('Alfa refund error ' . $errorcode)
                )
            );
        }

        // Alfa refund.do does not return a durable refund id. The official
        // getOrderStatusExtended v05+ response exposes refund referenceNumber.
        $history = $this->list_payment_refunds(
            $providerpaymentid,
            $currency
        );

        $providerrefundid = '';
        for ($index = count($history) - 1; $index >= 0; $index--) {
            if ($history[$index]->amountminor === $amountminor) {
                $providerrefundid = $history[$index]->providerrefundid;
                break;
            }
        }

        if ($providerrefundid === '') {
            // Compatibility fallback for older merchant status schemas.
            $providerrefundid = 'alfa-' . substr(
                hash(
                    'sha256',
                    $providerpaymentid
                    . '|' . $amountminor
                    . '|' . trim((string)($options['idempotency_key'] ?? ''))
                ),
                0,
                32
            );
        }

        return new ProviderRefundResult(
            $providerrefundid,
            'succeeded',
            $currency,
            $amountminor,
            [
                'providerpaymentid' => $providerpaymentid,
                'alfa_error_code' => $errorcode === '' ? '0' : $errorcode,
                'alfa_response' => $response,
            ]
        );
    }

    public function list_payment_refunds(
        string $providerpaymentid,
        string $currency
    ): array {
        $providerpaymentid = trim($providerpaymentid);
        $currency = strtoupper(trim($currency));

        if ($providerpaymentid === '' || $currency !== 'RUB') {
            return [];
        }

        $status = $this->get_status(null, $providerpaymentid);
        $errorcode = trim((string)($status['errorCode'] ?? ''));

        if ($errorcode !== '' && $errorcode !== '0') {
            throw new \moodle_exception(
                'paymentgatewayerror',
                'local_subscriptions',
                '',
                (string)($status['errorMessage'] ?? ('Alfa status error ' . $errorcode))
            );
        }

        $refunds = $status['refunds'] ?? [];
        if (!is_array($refunds)) {
            $refunds = [];
        }

        if ($refunds === []) {
            $paymentamountinfo = is_array(
                $status['paymentAmountInfo'] ?? null
            )
                ? $status['paymentAmountInfo']
                : [];

            $refundedamount = (int)(
                $paymentamountinfo['refundedAmount']
                ?? 0
            );

            if ($refundedamount > 0) {
                return [
                    new ProviderRefundResult(
                        'alfa-order-refunded-' . $providerpaymentid,
                        'succeeded',
                        $currency,
                        $refundedamount,
                        [
                            'providerpaymentid' => $providerpaymentid,
                            'aggregate' => true,
                            'paymentState' =>
                                $paymentamountinfo['paymentState']
                                ?? null,
                            'depositedAmount' =>
                                $paymentamountinfo['depositedAmount']
                                ?? null,
                            'refundedAmount' => $refundedamount,
                            'imported' => true,
                        ]
                    ),
                ];
            }

            return [];
        }

        if (
            $refunds !== []
            && (
                array_key_exists('referenceNumber', $refunds)
                || array_key_exists('amount', $refunds)
            )
        ) {
            $refunds = [$refunds];
        }

        $results = [];
        foreach (array_values($refunds) as $index => $refund) {
            if (!is_array($refund)) {
                continue;
            }

            $amount = (int)($refund['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $reference = trim((string)($refund['referenceNumber'] ?? ''));
            if ($reference === '') {
                $reference = 'alfa-history-' . substr(
                    hash(
                        'sha256',
                        $providerpaymentid . '|' . $index . '|' . $amount
                    ),
                    0,
                    32
                );
            }

            $results[] = new ProviderRefundResult(
                $reference,
                'succeeded',
                $currency,
                $amount,
                [
                    'providerpaymentid' => $providerpaymentid,
                    'referenceNumber' => $refund['referenceNumber'] ?? null,
                    'actionCode' => $refund['actionCode'] ?? null,
                    'imported' => true,
                ]
            );
        }

        return $results;
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
