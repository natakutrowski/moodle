<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

use local_subscriptions\commerce\currency\CommerceCurrencyAmount;

defined('MOODLE_INTERNAL') || die();

final class PayPalRestPaymentGateway implements PayPalPaymentGateway {
    public function __construct(
        private readonly PayPalGatewayConfiguration $configuration
    ) {
    }

    public function is_configured(): bool {
        return $this->configuration->is_configured();
    }

    /**
     * Read-only OAuth connectivity check for admin diagnostics.
     */
    public function test_connection(): void {
        $this->access_token();
    }

    public function create_order(
        PayPalOrderRequest $request
    ): PayPalOrderResponse {
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $request->get_reference(),
                'custom_id' => $request->get_reference(),
                'amount' => [
                    'currency_code' =>
                        $request->get_currency(),
                    'value' =>
                        CommerceCurrencyAmount::major_input_from_minor(
                            $request->get_amount_minor(),
                            $request->get_currency()
                        ),
                ],
            ]],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'user_action' => 'PAY_NOW',
                        'return_url' =>
                            $request->get_return_url(),
                        'cancel_url' =>
                            $request->get_cancel_url(),
                    ],
                ],
            ],
        ];

        $response = $this->request(
            'POST',
            '/v2/checkout/orders',
            $payload,
            [
                'PayPal-Request-Id: '
                    . $request->get_idempotency_key(),
                'Prefer: return=representation',
            ]
        );

        return $this->map_order($response);
    }

    public function retrieve_order(
        string $orderid
    ): PayPalOrderResponse {
        $orderid = $this->require_order_id($orderid);

        return $this->map_order(
            $this->request(
                'GET',
                '/v2/checkout/orders/'
                    . rawurlencode($orderid)
            )
        );
    }

    public function capture_order(
        string $orderid,
        string $idempotencykey
    ): PayPalOrderResponse {
        $orderid = $this->require_order_id($orderid);
        $idempotencykey = trim($idempotencykey);

        if ($idempotencykey === '') {
            throw new \coding_exception(
                'PayPal capture requires an idempotency key.'
            );
        }

        return $this->map_order(
            $this->request(
                'POST',
                '/v2/checkout/orders/'
                    . rawurlencode($orderid)
                    . '/capture',
                new \stdClass(),
                [
                    'PayPal-Request-Id: '
                        . $idempotencykey,
                    'Prefer: return=representation',
                ]
            )
        );
    }


    public function refund_capture(
        string $captureid,
        int $amountminor,
        string $currency,
        string $idempotencykey,
        ?string $note = null
    ): PayPalRefundResponse {
        $captureid = trim($captureid);
        $currency = strtoupper(trim($currency));
        $idempotencykey = trim($idempotencykey);

        if ($captureid === '') {
            throw new \coding_exception(
                'PayPal refund requires a capture id.'
            );
        }

        if ($amountminor <= 0) {
            throw new \coding_exception(
                'PayPal refund amount must be positive.'
            );
        }

        if ($idempotencykey === '') {
            throw new \coding_exception(
                'PayPal refund requires an idempotency key.'
            );
        }

        $payload = [
            'amount' => [
                'value' =>
                    CommerceCurrencyAmount::major_input_from_minor(
                        $amountminor,
                        $currency
                    ),
                'currency_code' => $currency,
            ],
        ];

        $note = trim((string)$note);
        if ($note !== '') {
            $payload['note_to_payer'] =
                \core_text::substr($note, 0, 255);
        }

        $response = $this->request(
            'POST',
            '/v2/payments/captures/'
                . rawurlencode($captureid)
                . '/refund',
            $payload,
            [
                'PayPal-Request-Id: ' . $idempotencykey,
                'Prefer: return=representation',
            ]
        );

        return $this->map_refund($response);
    }

    public function retrieve_refund(
        string $refundid
    ): PayPalRefundResponse {
        $refundid = trim($refundid);

        if ($refundid === '') {
            throw new \coding_exception(
                'PayPal refund id cannot be empty.'
            );
        }

        return $this->map_refund(
            $this->request(
                'GET',
                '/v2/payments/refunds/'
                    . rawurlencode($refundid)
            )
        );
    }

    public function retrieve_capture(
        string $captureid
    ): array {
        $captureid = trim($captureid);

        if ($captureid === '') {
            throw new \coding_exception(
                'PayPal capture id cannot be empty.'
            );
        }

        return $this->request(
            'GET',
            '/v2/payments/captures/'
                . rawurlencode($captureid)
        );
    }

    /**
     * Moodle's global curl client is declared in lib/filelib.php.
     * Return/checkout routes do not guarantee that file is already loaded.
     */
    private function new_curl(): \curl {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        return new \curl();
    }

    private function access_token(): string {
        $clientid = $this->configuration->get_client_id();
        $secret =
            $this->configuration->get_client_secret();

        if ($clientid === null || $secret === null) {
            throw new \RuntimeException(
                'PayPal credentials are not configured.'
            );
        }

        $curl = $this->new_curl();
        $curl->setHeader([
            'Accept: application/json',
            'Accept-Language: en_US',
            'Authorization: Basic '
                . base64_encode(
                    $clientid . ':' . $secret
                ),
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $raw = $curl->post(
            $this->configuration->get_api_base()
                . '/v1/oauth2/token',
            'grant_type=client_credentials',
            [
                'CURLOPT_TIMEOUT' => 30,
                'CURLOPT_CONNECTTIMEOUT' => 10,
            ]
        );

        $decoded = $this->decode_response(
            $raw,
            $curl,
            'paypal_oauth_failed'
        );

        $token = trim(
            (string)($decoded['access_token'] ?? '')
        );

        if ($token === '') {
            throw new \RuntimeException(
                'PayPal OAuth response contains no access token.'
            );
        }

        return $token;
    }

    private function request(
        string $method,
        string $path,
        mixed $payload = null,
        array $additionalheaders = []
    ): array {
        $curl = $this->new_curl();

        $headers = array_merge(
            [
                'Authorization: Bearer '
                    . $this->access_token(),
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            $additionalheaders
        );

        $curl->setHeader($headers);

        $url = $this->configuration->get_api_base()
            . $path;

        $options = [
            'CURLOPT_TIMEOUT' => 45,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ];

        $method = strtoupper(trim($method));

        if ($method === 'GET') {
            $raw = $curl->get(
                $url,
                [],
                $options
            );
        } else if ($method === 'POST') {
            $raw = $curl->post(
                $url,
                json_encode(
                    $payload,
                    JSON_THROW_ON_ERROR
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                ),
                $options
            );
        } else {
            throw new \coding_exception(
                'Unsupported PayPal REST method: '
                    . $method
            );
        }

        return $this->decode_response(
            $raw,
            $curl,
            'paypal_rest_request_failed'
        );
    }

    private function decode_response(
        mixed $raw,
        \curl $curl,
        string $code
    ): array {
        $info = $curl->get_info();
        $httpcode = (int)($info['http_code'] ?? 0);

        try {
            $decoded = json_decode(
                (string)$raw,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $exception) {
            throw new \RuntimeException(
                'PayPal returned an invalid JSON response.',
                0,
                $exception
            );
        }

        if (
            $httpcode < 200
            || $httpcode >= 300
        ) {
            $message = trim(
                (string)(
                    $decoded['message']
                    ?? $decoded['error_description']
                    ?? $decoded['error']
                    ?? 'PayPal API request failed.'
                )
            );

            $details = $decoded['details'] ?? null;
            if (
                is_array($details)
                && isset($details[0]['description'])
            ) {
                $message .= ' — '
                    . trim(
                        (string)$details[0]['description']
                    );
            }

            throw new \RuntimeException(
                $code
                . ': '
                . $message
                . ' [HTTP '
                . $httpcode
                . ']'
            );
        }

        return is_array($decoded)
            ? $decoded
            : [];
    }


    private function map_refund(
        array $refund
    ): PayPalRefundResponse {
        $id = trim((string)($refund['id'] ?? ''));
        $status = strtoupper(
            trim((string)($refund['status'] ?? 'PENDING'))
        );
        $currency = strtoupper(
            trim(
                (string)(
                    $refund['amount']['currency_code']
                    ?? ''
                )
            )
        );
        $value = trim(
            (string)(
                $refund['amount']['value']
                ?? ''
            )
        );

        if (
            $id === ''
            || $currency === ''
            || $value === ''
        ) {
            throw new \RuntimeException(
                'PayPal returned an incomplete refund payload.'
            );
        }

        $amount =
            CommerceCurrencyAmount::from_major_input(
                $value,
                $currency
            );

        return new PayPalRefundResponse(
            $id,
            $status,
            $currency,
            $amount->get_amount_minor(),
            [
                'paypal_refund' => $refund,
            ]
        );
    }

    private function map_order(
        array $order
    ): PayPalOrderResponse {
        $id = trim((string)($order['id'] ?? ''));
        $status = trim(
            (string)($order['status'] ?? 'PENDING')
        );

        $approvalurl = null;
        foreach ((array)($order['links'] ?? []) as $link) {
            if (
                is_array($link)
                && in_array(
                    strtolower((string)($link['rel'] ?? '')),
                    ['approve', 'payer-action'],
                    true
                )
            ) {
                $approvalurl = trim(
                    (string)($link['href'] ?? '')
                );
                if ($approvalurl !== '') {
                    break;
                }
            }
        }

        $captureid = null;
        $captures =
            $order['purchase_units'][0]['payments']['captures']
            ?? [];

        if (
            is_array($captures)
            && isset($captures[0]['id'])
        ) {
            $captureid = trim(
                (string)$captures[0]['id']
            );
        }

        return new PayPalOrderResponse(
            $id,
            $status,
            $approvalurl,
            $captureid,
            [
                'paypal_order' => $order,
            ]
        );
    }

    private function require_order_id(
        string $orderid
    ): string {
        $orderid = trim($orderid);

        if ($orderid === '') {
            throw new \coding_exception(
                'PayPal order id cannot be empty.'
            );
        }

        return $orderid;
    }
}
