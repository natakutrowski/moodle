<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Result of one Legacy-to-native Commerce migration attempt. */
final class CommerceLegacyMigrationResult {
    public const STATUS_MIGRATED = 'migrated';
    public const STATUS_ALREADY_PRESENT = 'already_present';
    public const STATUS_DRY_RUN = 'dry_run';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_FAILED = 'failed';

    private const VALID_STATUSES = [
        self::STATUS_MIGRATED,
        self::STATUS_ALREADY_PRESENT,
        self::STATUS_DRY_RUN,
        self::STATUS_SKIPPED,
        self::STATUS_INVALID,
        self::STATUS_FAILED,
    ];

    /** @param CommerceLegacyMigrationIssue[] $issues */
    public function __construct(
        private readonly string $legacyfamily,
        private readonly int $legacyid,
        private readonly string $status,
        private readonly ?string $purchaseuuid = null,
        private readonly ?string $purchasereference = null,
        private readonly array $issues = []
    ) {
        if (!in_array($legacyfamily, ['subscription', 'digital'], true)) {
            throw new \coding_exception('Unsupported Commerce migration Legacy family.');
        }
        if ($legacyid <= 0) {
            throw new \coding_exception('A Commerce migration Legacy identifier must be positive.');
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \coding_exception('Unsupported Commerce migration result status.');
        }
        foreach ($issues as $issue) {
            if (!$issue instanceof CommerceLegacyMigrationIssue) {
                throw new \coding_exception('Commerce migration issues must use CommerceLegacyMigrationIssue.');
            }
        }
    }

    public function get_legacy_family(): string { return $this->legacyfamily; }
    public function get_legacy_id(): int { return $this->legacyid; }
    public function get_status(): string { return $this->status; }
    public function get_purchase_uuid(): ?string { return $this->purchaseuuid; }
    public function get_purchase_reference(): ?string { return $this->purchasereference; }
    /** @return CommerceLegacyMigrationIssue[] */
    public function get_issues(): array { return $this->issues; }

    public function is_successful(): bool {
        return in_array($this->status, [
            self::STATUS_MIGRATED,
            self::STATUS_ALREADY_PRESENT,
            self::STATUS_DRY_RUN,
        ], true);
    }

    public function has_errors(): bool {
        foreach ($this->issues as $issue) {
            if ($issue->is_error()) {
                return true;
            }
        }
        return false;
    }

    public function to_array(): array {
        return [
            'legacyfamily' => $this->legacyfamily,
            'legacyid' => $this->legacyid,
            'status' => $this->status,
            'purchaseuuid' => $this->purchaseuuid,
            'purchasereference' => $this->purchasereference,
            'issues' => array_map(
                static fn(CommerceLegacyMigrationIssue $issue): array => $issue->to_array(),
                $this->issues
            ),
        ];
    }
}
