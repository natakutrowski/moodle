<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\idempotency;

defined('MOODLE_INTERNAL') || die();

final class CommerceIdempotencyRecord {
    public function __construct(
        private readonly int $id,
        private readonly string $scope,
        private readonly string $key,
        private readonly string $payloadhash,
        private readonly string $status,
        private readonly ?array $result,
        private readonly ?string $errormessage
    ) {
    }

    public function get_id(): int { return $this->id; }
    public function get_scope(): string { return $this->scope; }
    public function get_key(): string { return $this->key; }
    public function get_payload_hash(): string { return $this->payloadhash; }
    public function get_status(): string { return $this->status; }
    public function get_result(): ?array { return $this->result; }
    public function get_error_message(): ?string { return $this->errormessage; }
    public function is_completed(): bool { return $this->status === 'completed'; }
}
