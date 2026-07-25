<?php

namespace local_subscriptions\commerce\domain\value;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseType;

/**
 * Optional link between a native Commerce purchase and a Legacy record.
 *
 * This value object is a migration boundary, not the identity of the purchase.
 */
final class CommerceLegacyPurchaseReference {

    public function __construct(
        private readonly string $family,
        private readonly int $legacyid
    ) {
        if (!CommercePurchaseType::is_valid($family)) {
            throw new \coding_exception(
                'Unsupported Commerce Legacy purchase family: ' . $family
            );
        }

        if ($legacyid <= 0) {
            throw new \coding_exception(
                'A Commerce Legacy purchase identifier must be positive.'
            );
        }
    }

    public static function for_subscription(int $subscriptionid): self {
        return new self(
            CommercePurchaseType::SUBSCRIPTION,
            $subscriptionid
        );
    }

    public static function for_digital_purchase(int $purchaseid): self {
        return new self(
            CommercePurchaseType::DIGITAL,
            $purchaseid
        );
    }

    public function get_family(): string {
        return CommercePurchaseType::normalise($this->family);
    }

    public function get_legacy_id(): int {
        return $this->legacyid;
    }

    public function get_key(): string {
        return $this->get_family() . ':' . $this->legacyid;
    }

    public function equals(self $other): bool {
        return $this->get_family() === $other->get_family()
            && $this->legacyid === $other->legacyid;
    }

    public function is_subscription(): bool {
        return $this->get_family() === CommercePurchaseType::SUBSCRIPTION;
    }

    public function is_digital(): bool {
        return $this->get_family() === CommercePurchaseType::DIGITAL;
    }

    /** @return array<string, int|string> */
    public function to_array(): array {
        return [
            'family' => $this->get_family(),
            'legacyid' => $this->legacyid,
            'key' => $this->get_key(),
        ];
    }
}
