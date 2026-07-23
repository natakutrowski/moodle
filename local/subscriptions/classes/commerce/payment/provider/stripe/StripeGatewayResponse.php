<?php

namespace local_subscriptions\commerce\payment\provider\stripe;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalised response returned by the Stripe Legacy gateway.
 */
final class StripeGatewayResponse {

    public const STATUS_CREATED = 'created';
    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED =
        'partially_refunded';

    public function __construct(
        private readonly string $paymentid,
        private readonly string $status,
        private readonly ?string $checkouturl = null,
        private readonly ?string $errorcode = null,
        private readonly ?string $errormessage = null,
        private readonly array $metadata = []
    ) {
        if (trim($paymentid) === '') {
            throw new \coding_exception(
                'A Stripe gateway payment identifier cannot be empty.'
            );
        }

        if (trim($status) === '') {
            throw new \coding_exception(
                'A Stripe gateway response status cannot be empty.'
            );
        }
    }

    public function get_payment_id(): string {
        return trim($this->paymentid);
    }

    public function get_status(): string {
        return strtolower(trim($this->status));
    }

    public function get_checkout_url(): ?string {
        return $this->normalise_nullable_string(
            $this->checkouturl
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