<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider;

use local_subscriptions\commerce\payment\provider\paypal\PayPalGatewayConfiguration;
use local_subscriptions\commerce\payment\provider\paypal\PayPalRestPaymentGateway;
use local_subscriptions\payment\Provider;
use local_subscriptions\payment\stripe\StripeConfiguration;

defined('MOODLE_INTERNAL') || die();

final class CommercePaymentProviderConnectionTestService {
    public function test(string $provider): void {
        match ($provider) {
            Provider::STRIPE => $this->test_stripe(),
            Provider::ALFA => $this->test_alfa(),
            Provider::PAYPAL => (new PayPalRestPaymentGateway(new PayPalGatewayConfiguration()))->test_connection(),
            default => throw new \coding_exception('Unknown payment provider: ' . $provider),
        };
    }

    private function test_stripe(): void {
        global $CFG;
        $secret = trim(StripeConfiguration::secret_key());
        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }
        $autoload = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once($autoload);
        }
        if (!class_exists('\\Stripe\\Balance')) {
            throw new \RuntimeException('Stripe SDK is not available.');
        }
        \Stripe\Stripe::setApiKey($secret);
        \Stripe\Balance::retrieve();
    }

    private function test_alfa(): void {
        global $CFG;

        // Moodle's HTTP client lives in lib/filelib.php and is not guaranteed
        // to be loaded on admin pages before this service runs.
        require_once($CFG->libdir . '/filelib.php');

        $env = get_config('local_subscriptions', 'alfa_env') === 'live' ? 'live' : 'test';
        $prefix = 'alfa_' . $env . '_';
        $base = rtrim(trim((string)get_config('local_subscriptions', $prefix . 'api_base')), '/');
        $token = trim((string)get_config('local_subscriptions', $prefix . 'token'));
        $username = trim((string)get_config('local_subscriptions', $prefix . 'username'));
        $password = trim((string)get_config('local_subscriptions', $prefix . 'password'));
        if ($base === '' || ($token === '' && ($username === '' || $password === ''))) {
            throw new \RuntimeException('Alfa credentials are not configured.');
        }
        $fields = ['orderId' => '__campusfr_connection_test__'];
        if ($token !== '') {
            $fields['token'] = $token;
        } else {
            $fields['userName'] = $username;
            $fields['password'] = $password;
        }
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/x-www-form-urlencoded']);
        $raw = $curl->post($base . '/payment/rest/getOrderStatusExtended.do', http_build_query($fields, '', '&', PHP_QUERY_RFC3986), ['CURLOPT_TIMEOUT' => 30, 'CURLOPT_CONNECTTIMEOUT' => 10]);
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Alfa returned an unreadable response.');
        }
        $errorcode = (string)($decoded['errorCode'] ?? '');
        $message = trim((string)($decoded['errorMessage'] ?? ''));
        $normalizedmessage = mb_strtolower($message);

        // The probe deliberately uses a synthetic order id. "Order not found"
        // therefore proves that Alfa accepted the credentials and processed
        // the authenticated request far enough to look up the order.
        if (
            str_contains(
                $normalizedmessage,
                'заказ не найден'
            )
            || str_contains(
                $normalizedmessage,
                'order not found'
            )
        ) {
            return;
        }

        $authenticationfailure =
            str_contains(
                $normalizedmessage,
                'доступ запрещ'
            )
            || str_contains(
                $normalizedmessage,
                'access denied'
            )
            || str_contains(
                $normalizedmessage,
                'authentication'
            )
            || str_contains(
                $normalizedmessage,
                'password'
            )
            || str_contains(
                $normalizedmessage,
                'username'
            )
            || str_contains(
                $normalizedmessage,
                'user name'
            );

        if ($authenticationfailure) {
            throw new \RuntimeException(
                'Alfa authentication failed: '
                . (
                    $message !== ''
                        ? $message
                        : $errorcode
                )
            );
        }

        // A successful status response or another non-auth business response
        // also confirms that the endpoint is reachable with accepted credentials.
        return;
    }
}
