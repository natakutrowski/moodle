<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\command\dto;

defined('MOODLE_INTERNAL') || die();

final class CommerceCommandResult {
    public function __construct(
        private readonly string $family,
        private readonly int $legacyid,
        private readonly string $status,
        private readonly ?string $purchaseuuid = null,
        private readonly array $differences = [],
        private readonly ?string $errormessage = null,
        private readonly bool $replayed = false
    ) {
    }

    public function get_family(): string { return $this->family; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_status(): string { return $this->status; }
    public function get_purchase_uuid(): ?string { return $this->purchaseuuid; }
    public function get_differences(): array { return $this->differences; }
    public function get_error_message(): ?string { return $this->errormessage; }
    public function is_replayed(): bool { return $this->replayed; }

    public function is_successful(): bool {
        return in_array($this->status, ['created', 'updated', 'unchanged'], true);
    }

    public function with_replayed(bool $replayed = true): self {
        return new self(
            $this->family,
            $this->legacyid,
            $this->status,
            $this->purchaseuuid,
            $this->differences,
            $this->errormessage,
            $replayed
        );
    }

    public function to_array(): array {
        return [
            'family' => $this->family,
            'legacyid' => $this->legacyid,
            'status' => $this->status,
            'purchaseuuid' => $this->purchaseuuid,
            'differences' => $this->differences,
            'errormessage' => $this->errormessage,
            'replayed' => $this->replayed,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            (string)($data['family'] ?? ''),
            (int)($data['legacyid'] ?? 0),
            (string)($data['status'] ?? 'failed'),
            isset($data['purchaseuuid']) ? (string)$data['purchaseuuid'] : null,
            is_array($data['differences'] ?? null) ? $data['differences'] : [],
            isset($data['errormessage']) ? (string)$data['errormessage'] : null,
            !empty($data['replayed'])
        );
    }
}
