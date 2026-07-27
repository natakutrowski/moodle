<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable capabilities exposed by a Commerce product type.
 */
final class CommerceProductTypeCapabilities {

    public function __construct(
        private readonly bool $composable,
        private readonly bool $expandable,
        private readonly bool $directlypurchasable,
        private readonly bool $supportsentsitlements = true
    ) {
    }

    public function is_composable(): bool {
        return $this->composable;
    }

    public function is_expandable(): bool {
        return $this->expandable;
    }

    public function is_directly_purchasable(): bool {
        return $this->directlypurchasable;
    }

    public function supports_entitlements(): bool {
        return $this->supportsentsitlements;
    }

    public function to_array(): array {
        return [
            'composable' => $this->composable,
            'expandable' => $this->expandable,
            'directlypurchasable' => $this->directlypurchasable,
            'supportsentitlements' => $this->supportsentsitlements,
        ];
    }
}
