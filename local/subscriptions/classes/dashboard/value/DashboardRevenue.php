<?php

namespace local_subscriptions\dashboard\value;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\currency\Currency;

/**
 * Revenue total for one currency.
 */
final class DashboardRevenue {

    public function __construct(
        public readonly string $currency,
        public readonly float $subscriptions,
        public readonly float $digital
    ) {
    }

    /**
     * Combined CRM revenue.
     */
    public function total(): float {
        return $this->subscriptions + $this->digital;
    }

    /**
     * Export data for rendering or JSON.
     *
     * @return array{
     *     currency: string,
     *     subscriptions: float,
     *     digital: float,
     *     total: float
     * }
     */
    public function to_array(): array {
        return [
            'currency' => Currency::sanitize($this->currency),
            'subscriptions' => $this->subscriptions,
            'digital' => $this->digital,
            'total' => $this->total(),
        ];
    }
}