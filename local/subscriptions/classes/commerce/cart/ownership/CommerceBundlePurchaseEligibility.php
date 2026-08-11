<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\ownership;

defined('MOODLE_INTERNAL') || die();

/** Immutable bundle ownership diagnostic used before cart mutations. */
final class CommerceBundlePurchaseEligibility {
    /** @param string[] $ownedcomponents */
    public function __construct(
        private readonly int $componentcount,
        private readonly array $ownedcomponents
    ) {
    }

    public function is_bundle(): bool {
        return $this->componentcount > 0;
    }

    public function is_fully_owned(): bool {
        return $this->componentcount > 0 && count($this->ownedcomponents) >= $this->componentcount;
    }

    public function is_partially_owned(): bool {
        $ownedcount = count($this->ownedcomponents);
        return $ownedcount > 0 && $ownedcount < $this->componentcount;
    }

    public function get_component_count(): int {
        return $this->componentcount;
    }

    public function get_owned_count(): int {
        return count($this->ownedcomponents);
    }

    /** @return string[] */
    public function get_owned_components(): array {
        return $this->ownedcomponents;
    }
}
