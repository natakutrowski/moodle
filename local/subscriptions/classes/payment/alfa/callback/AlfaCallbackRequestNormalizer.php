<?php

declare(strict_types=1);

namespace local_subscriptions\payment\alfa\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalizes the small provider identity surface accepted by the public callback.
 *
 * External callback data must never be allowed to select an arbitrary Moodle DB
 * table or inject Commerce identity metadata. Only Alfa order identifiers are
 * retained; the provider status is then re-read from Alfa itself.
 */
final class AlfaCallbackRequestNormalizer {
    /**
     * @return array{orderId?:string,orderNumber?:string}
     */
    public function normalize(
        string $rawbody,
        array $query = [],
        array $post = []
    ): array {
        $body = $this->decode_body($rawbody);

        // Explicit form/query values take precedence over duplicate body fields.
        $data = array_merge($body, $query, $post);

        $orderid = $this->first_scalar($data, [
            'orderId',
            'orderid',
            'mdOrder',
            'mdorder',
        ]);
        $ordernumber = $this->first_scalar($data, [
            'orderNumber',
            'ordernumber',
            'order_number',
        ]);

        $normalized = [];
        if ($orderid !== '') {
            $normalized['orderId'] = $orderid;
        }
        if ($ordernumber !== '') {
            $normalized['orderNumber'] = $ordernumber;
        }

        if (!$normalized) {
            throw new \invalid_parameter_exception(
                'Alfa callback is missing orderId/mdOrder/orderNumber.'
            );
        }

        return $normalized;
    }

    private function decode_body(string $rawbody): array {
        $rawbody = trim($rawbody);
        if ($rawbody === '') {
            return [];
        }

        $json = json_decode($rawbody, true);
        if (is_array($json)) {
            return $json;
        }

        parse_str($rawbody, $form);
        return is_array($form) ? $form : [];
    }

    private function first_scalar(array $data, array $keys): string {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data) || !is_scalar($data[$key])) {
                continue;
            }
            $value = trim((string)$data[$key]);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }
}
