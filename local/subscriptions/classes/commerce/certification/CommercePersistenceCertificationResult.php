<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

/** Result of one Legacy-to-native persistence certification. */
final class CommercePersistenceCertificationResult {
    public const STATUS_CERTIFIED = 'certified';
    public const STATUS_DIFFERENT = 'different';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    private const VALID_STATUSES = [
        self::STATUS_CERTIFIED,
        self::STATUS_DIFFERENT,
        self::STATUS_FAILED,
        self::STATUS_SKIPPED,
    ];

    public function __construct(
        private readonly string $legacyfamily,
        private readonly int $legacyid,
        private readonly string $status,
        private readonly ?string $purchaseuuid,
        private readonly ?string $purchasereference,
        private readonly ?string $expectedhash,
        private readonly ?string $actualhash,
        private readonly int $durationms,
        private readonly bool $createdduringcertification = false,
        private readonly bool $cleanedup = false,
        private readonly array $differences = [],
        private readonly ?string $error = null
    ) {
        if (!in_array($legacyfamily, ['subscription', 'digital'], true)) {
            throw new \coding_exception('Unsupported Commerce certification Legacy family.');
        }
        if ($legacyid <= 0) {
            throw new \coding_exception('A Commerce certification Legacy identifier must be positive.');
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \coding_exception('Unsupported Commerce certification status.');
        }
    }

    public function get_legacy_family(): string { return $this->legacyfamily; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_status(): string { return $this->status; }
    public function get_purchase_uuid(): ?string { return $this->purchaseuuid; }
    public function get_purchase_reference(): ?string { return $this->purchasereference; }
    public function get_expected_hash(): ?string { return $this->expectedhash; }
    public function get_actual_hash(): ?string { return $this->actualhash; }
    public function get_duration_ms(): int { return $this->durationms; }
    public function was_created_during_certification(): bool { return $this->createdduringcertification; }
    public function was_cleaned_up(): bool { return $this->cleanedup; }
    public function get_differences(): array { return $this->differences; }
    public function get_error(): ?string { return $this->error; }
    public function is_certified(): bool { return $this->status === self::STATUS_CERTIFIED; }

    public function to_array(): array {
        return [
            'legacyfamily' => $this->legacyfamily,
            'legacyid' => $this->legacyid,
            'status' => $this->status,
            'purchaseuuid' => $this->purchaseuuid,
            'purchasereference' => $this->purchasereference,
            'expectedhash' => $this->expectedhash,
            'actualhash' => $this->actualhash,
            'durationms' => $this->durationms,
            'createdduringcertification' => $this->createdduringcertification,
            'cleanedup' => $this->cleanedup,
            'differences' => $this->differences,
            'error' => $this->error,
        ];
    }
}
