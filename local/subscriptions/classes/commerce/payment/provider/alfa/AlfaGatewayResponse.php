<?php

namespace local_subscriptions\commerce\payment\provider\alfa;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised response returned by the Alfa Legacy gateway.
 */
final class AlfaGatewayResponse {

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_PREAUTHORISED = 'preauthorised';
    public const STATUS_PAID = 'paid';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED =
        'partially_refunded';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    public function __construct(
        private readonly string $orderid,
        private readonly string $status,
        private readonly ?string $formurl = null,
        private readonly ?string $errorcode = null,
        private readonly ?string $errormessage = null,
        private readonly array $metadata = []
    ) {
        if (trim($orderid) === '') {
            throw new \coding_exception(
                'An Alfa order identifier cannot be empty.'
            );
        }

        if (trim($status) === '') {
            throw new \coding_exception(
                'An Alfa payment status cannot be empty.'
            );
        }
    }

    public function get_order_id(): string {
        return trim($this->orderid);
    }

    public function get_status(): string {
        return strtolower(trim($this->status));
    }

    public function get_form_url(): ?string {
        return $this->normalise_nullable_string(
            $this->formurl
        );
    }

    public function get_error_code(): ?string {
        return $this->normalise_nullable_string(
            $this->errorcode
        );
    }

    public function get_error_message(): ?string {
        return $this->normalise_nullable_string(
            $this->errormessage
        );
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    private function normalise_nullable_string(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}