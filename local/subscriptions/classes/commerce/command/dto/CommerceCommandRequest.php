<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command\dto;

defined('MOODLE_INTERNAL') || die();

final class CommerceCommandRequest {
    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $trigger,
        private readonly string $consumer = 'runtime',
        private readonly ?string $idempotencykey = null
    ) {
        if (!in_array($family, ['subscription', 'digital'], true)) {
            throw new \InvalidArgumentException('Unsupported Commerce family.');
        }

        if ($legacyid <= 0) {
            throw new \InvalidArgumentException('A positive Legacy Commerce identifier is required.');
        }

        if (trim($trigger) === '') {
            throw new \InvalidArgumentException('A Commerce command trigger is required.');
        }
    }

    public function get_family(): string {
        return $this->family;
    }

    public function get_legacy_id(): int {
        return $this->legacyid;
    }

    public function get_trigger(): string {
        return $this->trigger;
    }

    public function get_consumer(): string {
        return $this->consumer;
    }

    public function get_idempotency_key(): ?string {
        return $this->idempotencykey;
    }
}
