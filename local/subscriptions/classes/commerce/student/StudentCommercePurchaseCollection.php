<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\student;

defined('MOODLE_INTERNAL') || die();

/** Student-facing Commerce read model. */
final class StudentCommercePurchaseCollection {
    /**
     * @param \stdClass[] $subscriptions
     * @param \stdClass[] $digitalpurchases
     */
    public function __construct(
        private readonly array $subscriptions,
        private readonly array $digitalpurchases,
        private readonly string $source,
        private readonly array $differences = []
    ) {
    }

    public function get_subscriptions(): array { return $this->subscriptions; }
    public function get_digital_purchases(): array { return $this->digitalpurchases; }
    public function get_source(): string { return $this->source; }
    public function get_differences(): array { return $this->differences; }
    public function is_equivalent(): bool { return $this->differences === []; }
}
