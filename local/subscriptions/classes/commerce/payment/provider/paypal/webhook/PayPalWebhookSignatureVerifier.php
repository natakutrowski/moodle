<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal\webhook;

use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;

defined('MOODLE_INTERNAL') || die();

/**
 * Verifies PayPal REST webhooks through PayPal's verification endpoint.
 *
 * This deliberately preserves the exact raw payload until JSON decoding.
 */
final class PayPalWebhookSignatureVerifier {
    public function __construct(
        private readonly PayPalGatewayConfiguration $configuration
    ) {
    }

    public function verify(
        string $rawpayload,
        array $headers
    ): PayPalVerifiedWebhook {
        $event = $this->decode_event($rawpayload);

        $webhookid =
            $this->configuration->get_webhook_id();

        if ($webhookid === null) {
            throw new \RuntimeException(
                'PayPal webhook ID is not configured for the active environment.'
            );
        }

        $normalizedheaders =
            $this->normalize_headers($headers);

        $verificationpayload = [
            'auth_algo' =>
                $this->required_header(
                    $normalizedheaders,
                    'paypal-auth-algo'
                ),
            'cert_url' =>
                $this->required_header(
                    $normalizedheaders,
                    'paypal-cert-url'
                ),
            'transmission_id' =>
                $this->required_header(
                    $normalizedheaders,
                    'paypal-transmission-id'
                ),
            'transmission_sig' =>
                $this->required_header(
                    $normalizedheaders,
                    'paypal-transmission-sig'
                ),
            'transmission_time' =>
                $this->required_header(
                    $normalizedheaders,
                    'paypal-transmission-time'
                ),
            'webhook_id' => $webhookid,
            'webhook_event' => $event,
        ];

        $result = $this->post_json(
            '/v1/notifications/verify-webhook-signature',
            $verificationpayload
        );

        if (
            strtoupper(
                trim(
                    (string)(
                        $result['verification_status']
                        ?? ''
                    )
                )
            ) !== 'SUCCESS'
        ) {
            throw new \UnexpectedValueException(
                'PayPal webhook signature verification failed.'
            );
        }

        return new PayPalVerifiedWebhook(
            trim((string)($event['id'] ?? '')),
            trim((string)($event['event_type'] ?? '')),
            $event
        );
    }

    private function access_token(): string {
        $clientid =
            $this->configuration->get_client_id();
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

        $decoded = $this->decode_http_response(
            (string)$raw,
            $curl
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

    private function post_json(
        string $path,
        array $payload
    ): array {
        $curl = $this->new_curl();
        $curl->setHeader([
            'Authorization: Bearer '
                . $this->access_token(),
            'Accept: application/json',
            'Content-Type: application/json',
        ]);

        $raw = $curl->post(
            $this->configuration->get_api_base()
                . $path,
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            ),
            [
                'CURLOPT_TIMEOUT' => 30,
                'CURLOPT_CONNECTTIMEOUT' => 10,
            ]
        );

        return $this->decode_http_response(
            (string)$raw,
            $curl
        );
    }

    private function decode_http_response(
        string $raw,
        \curl $curl
    ): array {
        $info = $curl->get_info();
        $httpcode =
            (int)($info['http_code'] ?? 0);

        try {
            $decoded = json_decode(
                $raw,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException(
                'PayPal returned invalid JSON while verifying a webhook.',
                0,
                $exception
            );
        }

        if (
            $httpcode < 200
            || $httpcode >= 300
        ) {
            throw new \RuntimeException(
                'PayPal webhook verification API failed with HTTP '
                . $httpcode
                . ': '
                . trim(
                    (string)(
                        $decoded['message']
                        ?? 'Unknown PayPal error.'
                    )
                )
            );
        }

        return is_array($decoded)
            ? $decoded
            : [];
    }

    private function decode_event(
        string $rawpayload
    ): array {
        if (trim($rawpayload) === '') {
            throw new \UnexpectedValueException(
                'PayPal webhook payload is empty.'
            );
        }

        try {
            $event = json_decode(
                $rawpayload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException(
                'PayPal webhook payload is invalid JSON.',
                0,
                $exception
            );
        }

        if (!is_array($event)) {
            throw new \UnexpectedValueException(
                'PayPal webhook payload is invalid.'
            );
        }

        return $event;
    }

    private function normalize_headers(
        array $headers
    ): array {
        $normalized = [];

        foreach ($headers as $key => $value) {
            $normalized[
                strtolower(trim((string)$key))
            ] = trim((string)$value);
        }

        return $normalized;
    }

    private function required_header(
        array $headers,
        string $key
    ): string {
        $value = trim(
            (string)($headers[$key] ?? '')
        );

        if ($value === '') {
            throw new \UnexpectedValueException(
                'Missing PayPal webhook header: '
                . $key
            );
        }

        return $value;
    }

    private function new_curl(): \curl {
        global $CFG;

        require_once(
            $CFG->libdir . '/filelib.php'
        );

        return new \curl();
    }
}
