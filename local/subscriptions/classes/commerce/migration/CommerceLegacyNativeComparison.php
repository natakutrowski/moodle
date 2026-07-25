<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

/** Comparison between the expected Legacy projection and persisted native snapshot. */
final class CommerceLegacyNativeComparison {
    public const STATUS_EQUAL = 'equal';
    public const STATUS_DIFFERENT = 'different';
    public const STATUS_MISSING_NATIVE = 'missing_native';

    public function __construct(
        private readonly string $status,
        private readonly array $differences = []
    ) {
        if (!in_array($status, [self::STATUS_EQUAL, self::STATUS_DIFFERENT, self::STATUS_MISSING_NATIVE], true)) {
            throw new \coding_exception('Invalid Commerce Legacy/native comparison status.');
        }
    }

    public function get_status(): string { return $this->status; }
    public function get_differences(): array { return $this->differences; }
    public function is_equal(): bool { return $this->status === self::STATUS_EQUAL; }
}