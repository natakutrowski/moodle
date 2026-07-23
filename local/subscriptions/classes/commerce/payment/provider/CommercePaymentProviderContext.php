<?php

namespace local_subscriptions\commerce\payment\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Technical context supplied to a Commerce payment provider.
 *
 * No secret or provider credential should be stored in this value object.
 */
final class CommercePaymentProviderContext {

    public function __construct(
        private readonly string $idempotencykey,
        private readonly bool $live,
        private readonly array $metadata = [],
        private readonly ?int $createdat = null
    ) {
        if (
            trim($idempotencykey) === ''
            || strlen(trim($idempotencykey)) > 255
        ) {
            throw new \coding_exception(
                'A valid Commerce payment idempotency key is required.'
            );
        }
    }

    public function get_idempotency_key(): string {
        return trim(
            $this->idempotencykey
        );
    }

    public function is_live(): bool {
        return $this->live;
    }

    public function is_test(): bool {
        return !$this->live;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->metadata[$key]
            ?? $default;
    }

    public function get_created_at(): ?int {
        return $this->createdat;
    }
}