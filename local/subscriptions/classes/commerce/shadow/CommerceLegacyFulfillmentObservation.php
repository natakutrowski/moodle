<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow;

defined('MOODLE_INTERNAL') || die();

/** Read-only Legacy outcome captured by a future G5 runtime adapter. */
final class CommerceLegacyFulfillmentObservation {
    /** @param CommerceShadowEffect[] $effects */
    public function __construct(
        private readonly string $purchasereference,
        private readonly string $source,
        private readonly array $effects,
        private readonly bool $comparable = true,
        private readonly array $issues = []
    ) {
    }

    public function get_purchase_reference(): string { return $this->purchasereference; }
    public function get_source(): string { return $this->source; }
    public function get_effects(): array { return $this->effects; }
    public function is_comparable(): bool { return $this->comparable; }
    public function get_issues(): array { return $this->issues; }
}
