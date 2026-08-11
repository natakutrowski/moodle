<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\promotion\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;

/** Validates CRM promotion input before creating the immutable domain object. */
final class CommercePromotionValidator {
    /** @return array<string, string> */
    public function validate(array $data, MoodleCommercePromotionRepository $repository, ?int $currentid = null): array {
        $errors = [];
        $name = trim((string)($data['name'] ?? ''));
        $code = strtoupper(trim((string)($data['code'] ?? '')));
        $automatic = !empty($data['automatic']);
        $type = (string)($data['discounttype'] ?? '');
        $value = (int)($data['discountvalue'] ?? 0);
        $currency = strtoupper(trim((string)($data['currency'] ?? '')));
        $startsat = !empty($data['startsat']) ? (int)$data['startsat'] : null;
        $endsat = !empty($data['endsat']) ? (int)$data['endsat'] : null;

        if ($name === '') { $errors['name'] = 'required'; }
        if (!$automatic && $code === '') { $errors['code'] = 'required'; }
        if ($code !== '') {
            $existing = $repository->get_by_code($code);
            if ($existing !== null && $existing->get_id() !== $currentid) { $errors['code'] = 'duplicate'; }
        }
        if (!in_array($type, [CommercePromotion::TYPE_PERCENTAGE, CommercePromotion::TYPE_FIXED], true)) {
            $errors['discounttype'] = 'invalid';
        }
        if ($value <= 0 || ($type === CommercePromotion::TYPE_PERCENTAGE && $value > 10000)) {
            $errors['discountvalue'] = 'invalid';
        }
        if ($currency !== '' && !in_array($currency, (new CommerceCurrencyRegistry())->enabled(), true)) {
            $errors['currency'] = 'invalid';
        }
        if ((int)($data['minimumcartminor'] ?? 0) < 0) { $errors['minimumcartminor'] = 'invalid'; }
        foreach (['globalusagelimit', 'userusagelimit'] as $field) {
            if (($data[$field] ?? '') !== '' && (int)$data[$field] <= 0) { $errors[$field] = 'invalid'; }
        }
        if ($startsat !== null && $endsat !== null && $endsat <= $startsat) { $errors['endsat'] = 'invalid'; }
        return $errors;
    }
}
