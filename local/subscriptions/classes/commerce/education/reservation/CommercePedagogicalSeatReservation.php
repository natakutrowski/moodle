<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\reservation;

defined('MOODLE_INTERNAL') || die();

final class CommercePedagogicalSeatReservation {
    public const ACTIVE = 'active';
    public const RELEASED = 'released';
    public const EXPIRED = 'expired';
    public const CONSUMED = 'consumed';

    public function __construct(
        private readonly ?int $id,
        private readonly int $promotionid,
        private readonly int $productid,
        private readonly string $cartuuid,
        private readonly int $customerid,
        private readonly int $quantity,
        private readonly string $state,
        private readonly int $expiresat,
        private readonly ?int $checkoutstartedat,
        private readonly ?int $paymentstartedat,
        private readonly ?string $purchasereference,
        private readonly int $timecreated,
        private readonly int $timemodified
    ) {
        if ($promotionid <= 0 || $productid <= 0) {
            throw new \coding_exception(
                'Pedagogical reservation identifiers must be positive.'
            );
        }

        if (!preg_match('/^[a-f0-9]{32}$/', $cartuuid)) {
            throw new \coding_exception(
                'Pedagogical reservation cart UUID must contain 32 hexadecimal characters.'
            );
        }

        if ($customerid < 0) {
            throw new \coding_exception(
                'Pedagogical reservation customer identifier cannot be negative.'
            );
        }

        if ($quantity <= 0) {
            throw new \coding_exception(
                'Pedagogical reservation quantity must be positive.'
            );
        }

        if (!in_array(
            $state,
            [
                self::ACTIVE,
                self::RELEASED,
                self::EXPIRED,
                self::CONSUMED,
            ],
            true
        )) {
            throw new \coding_exception(
                'Unknown pedagogical reservation state.'
            );
        }
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function get_promotion_id(): int {
        return $this->promotionid;
    }

    public function get_product_id(): int {
        return $this->productid;
    }

    public function get_cart_uuid(): string {
        return $this->cartuuid;
    }

    public function get_customer_id(): int {
        return $this->customerid;
    }

    public function get_quantity(): int {
        return $this->quantity;
    }

    public function get_state(): string {
        return $this->state;
    }

    public function get_expires_at(): int {
        return $this->expiresat;
    }

    public function get_checkout_started_at(): ?int {
        return $this->checkoutstartedat;
    }

    public function get_payment_started_at(): ?int {
        return $this->paymentstartedat;
    }

    public function get_purchase_reference(): ?string {
        return $this->purchasereference;
    }

    public function get_time_created(): int {
        return $this->timecreated;
    }

    public function get_time_modified(): int {
        return $this->timemodified;
    }

    public function is_active_at(int $now): bool {
        return $this->state === self::ACTIVE
            && $this->expiresat > $now;
    }
}
