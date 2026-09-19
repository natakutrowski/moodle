<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\payment\provider\paypal;

defined('MOODLE_INTERNAL') || die();

final class PayPalOrderResponse {
    public function __construct(
        private readonly string $orderid,
        private readonly string $status,
        private readonly ?string $approvalurl = null,
        private readonly ?string $captureid = null,
        private readonly array $metadata = []
    ) {
        if (trim($orderid) === '') {
            throw new \coding_exception(
                'A PayPal order response requires an order id.'
            );
        }
        if (trim($status) === '') {
            throw new \coding_exception(
                'A PayPal order response requires a status.'
            );
        }
    }

    public function get_order_id(): string {
        return trim($this->orderid);
    }

    public function get_status(): string {
        return strtoupper(trim($this->status));
    }

    public function get_approval_url(): ?string {
        $value = trim((string)$this->approvalurl);
        return $value !== '' ? $value : null;
    }

    public function get_capture_id(): ?string {
        $value = trim((string)$this->captureid);
        return $value !== '' ? $value : null;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }
}
