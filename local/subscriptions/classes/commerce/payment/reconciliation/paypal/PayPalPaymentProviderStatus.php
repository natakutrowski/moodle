<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\reconciliation\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalPaymentProviderStatus {
    public function __construct(
        public readonly string $orderid,
        public readonly string $environment,
        public readonly string $orderstatus,
        public readonly ?string $captureid,
        public readonly ?string $capturestatus,
        public readonly ?int $amountminor,
        public readonly ?string $currency,
        public readonly int $refundedminor,
        public readonly ?int $netminor,
        public readonly array $orderpayload = [],
        public readonly array $capturepayload = []
    ) {
    }

    public function has_completed_capture(): bool {
        return strtoupper(
            trim((string)$this->capturestatus)
        ) === 'COMPLETED';
    }

    public function is_refunded_state(): bool {
        return in_array(
            strtoupper(
                trim((string)$this->capturestatus)
            ),
            ['PARTIALLY_REFUNDED', 'REFUNDED'],
            true
        ) || $this->refundedminor > 0;
    }

    public function is_financially_settled(): bool {
        if ($this->amountminor === null) {
            return false;
        }

        if ($this->has_completed_capture()) {
            return true;
        }

        if (!$this->is_refunded_state()) {
            return false;
        }

        if ($this->netminor === null) {
            return false;
        }

        return $this->netminor
            === max(
                0,
                $this->amountminor
                    - $this->refundedminor
            );
    }
}
