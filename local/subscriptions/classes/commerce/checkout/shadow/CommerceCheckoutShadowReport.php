<?php

namespace local_subscriptions\commerce\checkout\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only checkout shadow report.
 */
final class CommerceCheckoutShadowReport {

    /** @param CommerceCheckoutComparison[] $comparisons */
    public function __construct(
        private readonly string $reference,
        private readonly array $comparisons,
        private readonly array $errors = []
    ) {
    }

    public function get_reference(): string {
        return $this->reference;
    }

    public function get_comparisons(): array {
        return $this->comparisons;
    }

    public function get_errors(): array {
        return $this->errors;
    }

    public function is_compatible(): bool {
        if ($this->errors !== []) {
            return false;
        }

        foreach ($this->comparisons as $comparison) {
            if (!$comparison->matches()) {
                return false;
            }
        }

        return true;
    }

    public function to_array(): array {
        return [
            'reference' => $this->reference,
            'compatible' => $this->is_compatible(),
            'comparisons' => array_map(
                static fn(CommerceCheckoutComparison $comparison): array => $comparison->to_array(),
                $this->comparisons
            ),
            'errors' => $this->errors,
        ];
    }
}
