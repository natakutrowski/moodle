<?php

namespace local_subscriptions\commerce\checkout\shadow;

defined('MOODLE_INTERNAL') || die();

/**
 * One comparison between Legacy checkout data and the Commerce projection.
 */
final class CommerceCheckoutComparison {

    public function __construct(
        private readonly string $field,
        private readonly mixed $legacyvalue,
        private readonly mixed $commercevalue
    ) {
    }

    public function get_field(): string {
        return $this->field;
    }

    public function matches(): bool {
        return $this->legacyvalue === $this->commercevalue;
    }

    public function to_array(): array {
        return [
            'field' => $this->field,
            'matches' => $this->matches(),
            'legacy' => $this->legacyvalue,
            'commerce' => $this->commercevalue,
        ];
    }
}
