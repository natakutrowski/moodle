<?php
namespace local_subscriptions\payment\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Opérations admin Alfa : capture (deposit), cancel (reverse), refund.
 * Utilisation typique dans un back-office :
 *   AlfaAdmin::capture($orderId, $amountMinor);
 *   AlfaAdmin::cancel($orderId);
 *   AlfaAdmin::refund($orderId, $amountMinor);
 */
final class AlfaAdmin {

    private static function base(): string {
        $base = rtrim(get_config('local_subscriptions', 'alfa_api_base') ?: '', '/');
        if (!$base) {
            throw new \moodle_exception('configmissing', 'local_subscriptions', '', 'alfa_api_base');
        }
        return $base;
    }

    private static function auth(): array {
        $token    = get_config('local_subscriptions', 'alfa_token') ?: null;
        $username = get_config('local_subscriptions', 'alfa_username') ?: null;
        $password = get_config('local_subscriptions', 'alfa_password') ?: null;
        return [$token, $username, $password];
    }

    private static function post(string $path, array $fields): array {
        $url = self::base() . $path;
        [$token, $username, $password] = self::auth();

        $ch = curl_init($url);
        if (!empty($token)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            $payload = $fields;
        } else {
            $auth = [];
            if ($username !== null) { $auth['userName'] = $username; }
            if ($password !== null) { $auth['password'] = $password; }
            $payload = array_merge($auth, $fields);
        }
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) { $err = curl_error($ch); curl_close($ch); throw new \moodle_exception('paymentgatewayerror', 'local_subscriptions', '', 'CURL: ' . $err); }
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

    /**
     * Capture d'une préautorisation (deux-étapes).
     * @param string $orderId    Alfa orderId (stocké dans sessionid du PR)
     * @param int|null $amountMinor Montant en kopecks (facultatif ; si null => plein montant)
     */
    public static function capture(string $orderId, ?int $amountMinor = null): array {
        $fields = ['orderId' => $orderId];
        if ($amountMinor !== null) {
            $fields['amount'] = $amountMinor;
        }
        return self::post('/payment/rest/deposit.do', $fields);
    }

    /**
     * Annulation d'une autorisation (avant capture).
     */
    public static function cancel(string $orderId): array {
        return self::post('/payment/rest/reverse.do', ['orderId' => $orderId]);
    }

    /**
     * Remboursement après paiement.
     * @param string $orderId      Alfa orderId
     * @param int    $amountMinor  Montant remboursé en kopecks
     */
    public static function refund(string $orderId, int $amountMinor): array {
        return self::post('/payment/rest/refund.do', [
            'orderId' => $orderId,
            'amount'  => $amountMinor,
        ]);
    }
}
