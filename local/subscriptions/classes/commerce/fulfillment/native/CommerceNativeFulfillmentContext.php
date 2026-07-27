<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable execution context for one Native Commerce grant fulfillment.
 */
final class CommerceNativeFulfillmentContext {
    private readonly string $executionreference;
    private readonly string $source;

    public function __construct(
        string $executionreference,
        private readonly int $triggeredat,
        private readonly ?int $actoruserid = null,
        string $source = 'runtime',
        private readonly bool $dryrun = false,
        private readonly array $metadata = []
    ) {
        $executionreference = trim($executionreference);
        $source = strtolower(trim($source));

        if ($executionreference === '') {
            throw new \coding_exception('A Native Commerce fulfillment execution reference cannot be empty.');
        }

        if ($triggeredat <= 0) {
            throw new \coding_exception('A Native Commerce fulfillment execution timestamp must be positive.');
        }

        if ($actoruserid !== null && $actoruserid <= 0) {
            throw new \coding_exception('A Native Commerce fulfillment actor user identifier must be positive.');
        }

        if (!preg_match('/^[a-z][a-z0-9_.-]{1,63}$/', $source)) {
            throw new \coding_exception('Invalid Native Commerce fulfillment source.');
        }

        $this->executionreference = $executionreference;
        $this->source = $source;
    }

    public static function runtime(
        string $executionreference,
        int $triggeredat,
        ?int $actoruserid = null,
        string $source = 'runtime',
        array $metadata = []
    ): self {
        return new self(
            $executionreference,
            $triggeredat,
            $actoruserid,
            $source,
            false,
            $metadata
        );
    }

    public static function dry_run(
        string $executionreference,
        int $triggeredat,
        ?int $actoruserid = null,
        string $source = 'audit',
        array $metadata = []
    ): self {
        return new self(
            $executionreference,
            $triggeredat,
            $actoruserid,
            $source,
            true,
            $metadata
        );
    }

    public function get_execution_reference(): string {
        return $this->executionreference;
    }

    public function get_triggered_at(): int {
        return $this->triggeredat;
    }

    public function get_actor_user_id(): ?int {
        return $this->actoruserid;
    }

    public function get_source(): string {
        return $this->source;
    }

    public function is_dry_run(): bool {
        return $this->dryrun;
    }

    public function get_metadata(): array {
        return $this->metadata;
    }

    public function get_metadata_value(string $key, mixed $default = null): mixed {
        return $this->metadata[$key] ?? $default;
    }
}
