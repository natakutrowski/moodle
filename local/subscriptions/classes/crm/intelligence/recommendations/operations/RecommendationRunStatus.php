<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations;

defined('MOODLE_INTERNAL') || die();

/**
 * Statuses of a Recommendation Engine batch run.
 */
final class RecommendationRunStatus {

    public const RUNNING = 'running';
    public const COMPLETED = 'completed';
    public const PARTIAL = 'partial';
    public const FAILED = 'failed';
    public const SKIPPED = 'skipped';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::RUNNING,
            self::COMPLETED,
            self::PARTIAL,
            self::FAILED,
            self::SKIPPED,
        ];
    }

    public static function is_valid(
        string $status
    ): bool {
        return in_array(
            $status,
            self::all(),
            true
        );
    }

    public static function is_terminal(
        string $status
    ): bool {
        return in_array(
            $status,
            [
                self::COMPLETED,
                self::PARTIAL,
                self::FAILED,
                self::SKIPPED,
            ],
            true
        );
    }
}