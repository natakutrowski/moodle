<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\checkout;

defined('MOODLE_INTERNAL') || die();

/** Immutable presentation context consumed by the public Subscription checkout page. */
final class CommerceSubscriptionCheckoutPageResult {
    /**
     * @param array<int, array<string, mixed>> $options
     */
    public function __construct(
        private readonly \stdClass $plan,
        private readonly ?\stdClass $currentSubscription,
        private readonly ?\stdClass $currentPlan,
        private readonly array $options,
        private readonly string $requestedCurrency,
        private readonly string $usedCurrency,
        private readonly float $basePrice,
        private readonly bool $requestedCurrencyAvailable,
        private readonly bool $discountOpen,
        private readonly int $discountPercent,
        private readonly int $discountDeadline,
        private readonly float $finalPurchasePrice,
        private readonly bool $alreadyCovered
    ) {
    }

    public function get_plan(): \stdClass {
        return $this->plan;
    }

    public function get_current_subscription(): ?\stdClass {
        return $this->currentSubscription;
    }

    public function get_current_plan(): ?\stdClass {
        return $this->currentPlan;
    }

    /** @return array<int, array<string, mixed>> */
    public function get_options(): array {
        return $this->options;
    }

    public function get_requested_currency(): string {
        return $this->requestedCurrency;
    }

    public function get_used_currency(): string {
        return $this->usedCurrency;
    }

    public function get_base_price(): float {
        return $this->basePrice;
    }

    public function is_requested_currency_available(): bool {
        return $this->requestedCurrencyAvailable;
    }

    public function is_discount_open(): bool {
        return $this->discountOpen;
    }

    public function get_discount_percent(): int {
        return $this->discountPercent;
    }

    public function get_discount_deadline(): int {
        return $this->discountDeadline;
    }

    public function get_final_purchase_price(): float {
        return $this->finalPurchasePrice;
    }

    public function is_already_covered(): bool {
        return $this->alreadyCovered;
    }
}
