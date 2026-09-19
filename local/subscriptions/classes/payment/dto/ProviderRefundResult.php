<?php
declare(strict_types=1);

namespace local_subscriptions\payment\dto;

defined('MOODLE_INTERNAL') || die();

final class ProviderRefundResult {
    public function __construct(
        public readonly string $providerrefundid,
        public readonly string $status,
        public readonly string $currency,
        public readonly int $amountminor,
        public readonly array $metadata = []
    ) {
        if (trim($providerrefundid) === '') {
            throw new \coding_exception('A provider refund result requires an identifier.');
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper(trim($currency)))) {
            throw new \coding_exception('A provider refund result requires an ISO currency.');
        }
        if ($amountminor <= 0) {
            throw new \coding_exception('A provider refund result amount must be positive.');
        }
    }
}
