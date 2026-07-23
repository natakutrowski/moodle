<?php

namespace local_subscriptions\commerce\fulfillment;

defined('MOODLE_INTERNAL') || die();

/**
 * Provider-independent operation to execute after payment confirmation.
 */
final class CommerceFulfillmentOperation {

    public function __construct(
        private readonly string $reference,
        private readonly string $key,
        private readonly array $metadata = []
    ) {
        if (trim($reference) === '') {
            throw new \coding_exception(
                'A Commerce fulfillment reference cannot be empty.'
            );
        }

        if (
            trim($key) === ''
            || !preg_match(
                '/^[a-z][a-z0-9_]*$/',
                strtolower(trim($key))
            )
        ) {
            throw new \coding_exception(
                'Invalid Commerce fulfillment key.'
            );
        }
    }

    public function get_reference(): string {
        return trim($this->reference);
    }

    public function get_key(): string {
        return strtolower(trim($this->key));
    }

    public function get_idempotency_key(): string {
        return $this->get_reference() . ':' . $this->get_key();
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
