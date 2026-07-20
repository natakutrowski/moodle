<?php

namespace local_subscriptions\digital\providers;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\dto\DigitalPaymentProviderStatus;

/**
 * Checks Alfa Bank payment order statuses.
 */
final class AlfaPaymentStatusProvider
    implements DigitalPaymentStatusProvider {

    private const CONNECT_TIMEOUT = 5;

    private const REQUEST_TIMEOUT = 15;

    public function provider_key(): string {
        return 'alfa';
    }

    public function supports(
        string $provider
    ): bool {
        return
            strtolower(trim($provider)) ===
            $this->provider_key();
    }

    public function check(
        \stdClass $paymentrequest
    ): DigitalPaymentProviderStatus {
        $sessionid =
            trim(
                (string)(
                    $paymentrequest->sessionid
                    ?? ''
                )
            );

        if ($sessionid === '') {
            return
                DigitalPaymentProviderStatus::unknown(
                    'No sessionid available.'
                );
        }

        if (!function_exists('curl_init')) {
            return
                DigitalPaymentProviderStatus::error(
                    'PHP cURL extension is unavailable.'
                );
        }

        $environment =
            get_config(
                'local_subscriptions',
                'alfa_env'
            ) ?: 'test';

        $environment =
            $environment === 'live'
                ? 'live'
                : 'test';

        $baseurl =
            rtrim(
                (string)(
                    get_config(
                        'local_subscriptions',
                        'alfa_' .
                        $environment .
                        '_api_base'
                    ) ?: ''
                ),
                '/'
            );

        $username =
            (string)(
                get_config(
                    'local_subscriptions',
                    'alfa_' .
                    $environment .
                    '_username'
                ) ?: ''
            );

        $password =
            (string)(
                get_config(
                    'local_subscriptions',
                    'alfa_' .
                    $environment .
                    '_password'
                ) ?: ''
            );

        $token =
            (string)(
                get_config(
                    'local_subscriptions',
                    'alfa_' .
                    $environment .
                    '_token'
                ) ?: ''
            );

        if ($baseurl === '') {
            return
                DigitalPaymentProviderStatus::error(
                    'Missing Alfa API base.'
                );
        }

        if (
            $token === '' &&
            $username === '' &&
            $password === ''
        ) {
            return
                DigitalPaymentProviderStatus::error(
                    'Missing Alfa API credentials.'
                );
        }

        $payload = [
            'orderId' => $sessionid,
        ];

        if ($token !== '') {
            $payload['token'] = $token;
        } else {
            if ($username !== '') {
                $payload['userName'] =
                    $username;
            }

            if ($password !== '') {
                $payload['password'] =
                    $password;
            }
        }

        $handle =
            curl_init(
                $baseurl .
                '/payment/rest/getOrderStatusExtended.do'
            );

        if ($handle === false) {
            return
                DigitalPaymentProviderStatus::error(
                    'Unable to initialise Alfa HTTP request.'
                );
        }

        curl_setopt_array(
            $handle,
            [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS =>
                    http_build_query(
                        $payload,
                        '',
                        '&',
                        PHP_QUERY_RFC3986
                    ),
                CURLOPT_CONNECTTIMEOUT =>
                    self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT =>
                    self::REQUEST_TIMEOUT,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ],
            ]
        );

        $rawresponse =
            curl_exec($handle);

        $curlerror =
            curl_error($handle);

        $httpcode =
            (int)curl_getinfo(
                $handle,
                CURLINFO_RESPONSE_CODE
            );

        curl_close($handle);

        if ($rawresponse === false) {
            return
                DigitalPaymentProviderStatus::error(
                    'Alfa HTTP request failed' .
                    (
                        $curlerror !== ''
                            ? ': ' .
                                $this->sanitize_reason(
                                    $curlerror
                                )
                            : '.'
                    )
                );
        }

        if (
            $httpcode < 200 ||
            $httpcode >= 300
        ) {
            return
                DigitalPaymentProviderStatus::error(
                    'Alfa HTTP status: ' .
                    $httpcode
                );
        }

        $data =
            json_decode(
                (string)$rawresponse,
                true
            );

        if (!is_array($data)) {
            $parsed = [];

            parse_str(
                (string)$rawresponse,
                $parsed
            );

            if ($parsed !== []) {
                $data = $parsed;
            }
        }

        if (!is_array($data)) {
            return
                DigitalPaymentProviderStatus::error(
                    'Invalid Alfa response.'
                );
        }

        $orderstatus =
            array_key_exists(
                'orderStatus',
                $data
            )
                ? (int)$data['orderStatus']
                : null;

        $reason =
            $data['actionCodeDescription']
            ?? $data['errorMessage']
            ?? $data['error']
            ?? '';

        $reason =
            $this->sanitize_reason(
                (string)$reason
            );

        if ($orderstatus === 2) {
            return
                DigitalPaymentProviderStatus::paid();
        }

        if ($orderstatus === 6) {
            return
                DigitalPaymentProviderStatus::declined(
                    $reason !== ''
                        ? $reason
                        : 'Payment declined.'
                );
        }

        if ($orderstatus === 0) {
            return
                DigitalPaymentProviderStatus::pending(
                    $reason !== ''
                        ? $reason
                        : 'Registered but not paid.'
                );
        }

        return
            DigitalPaymentProviderStatus::pending(
                $reason !== ''
                    ? $reason
                    : 'Alfa orderStatus: ' .
                        (
                            $orderstatus !== null
                                ? (string)$orderstatus
                                : 'unknown'
                        )
            );
    }

    private function sanitize_reason(
        string $reason
    ): string {
        $reason = trim($reason);

        if ($reason === '') {
            return '';
        }

        $reason =
            preg_replace(
                '/([?&](?:token|password|userName)=)[^&\s]+/i',
                '$1[redacted]',
                $reason
            ) ?? $reason;

        $reason =
            preg_replace(
                '/((?:token|password|userName)\s*[=:]\s*)[^\s,;]+/i',
                '$1[redacted]',
                $reason
            ) ?? $reason;

        if (
            \core_text::strlen($reason) >
            1000
        ) {
            $reason =
                \core_text::substr(
                    $reason,
                    0,
                    1000
                ) .
                '…';
        }

        return $reason;
    }
}