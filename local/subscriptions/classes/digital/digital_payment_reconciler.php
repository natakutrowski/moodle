<?php
namespace local_subscriptions\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\payment\dto\InternalEvent;

class digital_payment_reconciler {

    public static function reconcile_pending(array $options = []): array {
        global $DB;

        $limit = (int)($options['limit'] ?? 5);
        $minage = (int)($options['minage'] ?? 300);
        $maxage = (int)($options['maxage'] ?? 2 * DAYSECS);

        $now = time();

        $params = [
            'status' => 'pending',
            'mintime' => $now - $minage,
            'maxtime' => $now - $maxage,
        ];

        $sql = "
            SELECT *
              FROM {subscription_digital_payment_request}
             WHERE status = :status
               AND sessionid IS NOT NULL
               AND sessionid <> ''
               AND creation_date <= :mintime
               AND creation_date >= :maxtime
          ORDER BY creation_date DESC, id DESC
        ";

        $records = $DB->get_records_sql($sql, $params, 0, $limit);

        $result = [
            'reconciled' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($records as $pr) {
            try {
                $providerstatus = self::check_provider_status($pr);
                $status = strtoupper($providerstatus['status'] ?? 'UNKNOWN');
                $reason = $providerstatus['reason'] ?? '';

                if ($status === 'PAID') {
                    $event = new InternalEvent('checkout_completed', [
                        'payment_request_id' => (string)$pr->id,
                        'currency' => $pr->currency,
                        'amount_minor' => (int)$pr->amount_minor,
                        'meta' => [
                            'payment_context' => 'digital_product',
                            'provider' => $pr->payment_provider,
                            'session' => $pr->sessionid,
                            'orderId' => $pr->sessionid,
                        ],
                    ]);

                    digital_payment_service::on_checkout_completed($event);
                    $result['reconciled']++;
                    continue;
                }

                if (
                    $status === 'DECLINED'
                    || ($status === 'UNKNOWN' && stripos($reason, 'No sessionid') !== false)
                    || ($status === 'PENDING' && stripos($reason, 'payment_status: unpaid') !== false)
                ) {
                    $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                        'id' => $pr->id,
                        'status' => 'failed',
                        'last_error' => '[cron_reconcile] Provider failed/unpaid: ' . $reason,
                        'last_update' => $now,
                    ]);

                    $result['failed']++;
                    continue;
                }

                // PENDING / UNKNOWN / ERROR: keep as pending, but store the last provider status.
                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'last_error' => '[cron_reconcile] Provider status: ' . $status .
                        ($reason !== '' ? ' - ' . $reason : ''),
                    'last_update' => $now,
                ]);

                $result['skipped']++;
            } catch (\Throwable $e) {
                $result['errors']++;

                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'last_error' => '[cron_reconcile] ' . $e->getMessage(),
                    'last_update' => time(),
                ]);
            }
        }

        return $result;
    }

    private static function check_provider_status(\stdClass $pr): array {
        if ($pr->payment_provider === 'stripe') {
            return self::check_stripe($pr);
        }

        if ($pr->payment_provider === 'alfa') {
            return self::check_alfa($pr);
        }

        return [
            'status' => 'UNKNOWN',
            'reason' => 'Unsupported provider: ' . ($pr->payment_provider ?? ''),
        ];
    }

    private static function check_stripe(\stdClass $pr): array {
        global $CFG;

        $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
        $env = ($env === 'live') ? 'live' : 'test';

        $secret = get_config('local_subscriptions', "stripe_{$env}_secret") ?: '';

        if ($secret === '') {
            return [
                'status' => 'ERROR',
                'reason' => 'Missing Stripe secret key for env: ' . $env,
            ];
        }

        $autoload = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return [
                'status' => 'ERROR',
                'reason' => 'Stripe SDK autoload not found.',
            ];
        }

        require_once($autoload);

        \Stripe\Stripe::setApiKey($secret);

        $session = \Stripe\Checkout\Session::retrieve($pr->sessionid);

        $paymentstatus = $session->payment_status ?? '';
        $status = $session->status ?? '';

        if ($paymentstatus === 'paid') {
            return [
                'status' => 'PAID',
                'reason' => '',
            ];
        }

        if ($status === 'expired') {
            return [
                'status' => 'DECLINED',
                'reason' => 'Stripe Checkout session expired.',
            ];
        }

        return [
            'status' => 'PENDING',
            'reason' => 'Stripe status: ' . $status . ' / payment_status: ' . $paymentstatus,
        ];
    }

    private static function check_alfa(\stdClass $pr): array {
        $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
        $env = ($env === 'live') ? 'live' : 'test';

        $base = rtrim((string)(get_config('local_subscriptions', "alfa_{$env}_api_base") ?: ''), '/');
        $username = get_config('local_subscriptions', "alfa_{$env}_username") ?: '';
        $password = get_config('local_subscriptions', "alfa_{$env}_password") ?: '';
        $token = get_config('local_subscriptions', "alfa_{$env}_token") ?: '';

        if ($base === '') {
            return [
                'status' => 'ERROR',
                'reason' => 'Missing Alfa API base.',
            ];
        }

        $payload = [
            'orderId' => $pr->sessionid,
        ];

        if ($token !== '') {
            $payload['token'] = $token;
        } else {
            if ($username !== '') {
                $payload['userName'] = $username;
            }

            if ($password !== '') {
                $payload['password'] = $password;
            }
        }

        $ch = curl_init($base . '/payment/rest/getOrderStatusExtended.do');

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);

            return [
                'status' => 'ERROR',
                'reason' => 'CURL error: ' . $err,
            ];
        }

        curl_close($ch);

        $data = json_decode($raw, true);

        if (!is_array($data)) {
            parse_str($raw, $data);
        }

        if (!is_array($data)) {
            return [
                'status' => 'ERROR',
                'reason' => 'Invalid Alfa response.',
            ];
        }

        $orderstatus = isset($data['orderStatus']) ? (int)$data['orderStatus'] : null;

        $reason = $data['actionCodeDescription']
            ?? $data['errorMessage']
            ?? $data['error']
            ?? '';

        if ($orderstatus === 2) {
            return [
                'status' => 'PAID',
                'reason' => '',
            ];
        }

        if ($orderstatus === 6) {
            return [
                'status' => 'DECLINED',
                'reason' => $reason ?: 'Payment declined.',
            ];
        }

        if ($orderstatus === 0) {
            return [
                'status' => 'PENDING',
                'reason' => $reason ?: 'Registered but not paid.',
            ];
        }

        return [
            'status' => 'PENDING',
            'reason' => $reason ?: 'Alfa orderStatus: ' . var_export($orderstatus, true),
        ];
    }
}