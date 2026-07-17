<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle statuses of a Customer Success plan step.
 */
final class CustomerSuccessPlanStepStatus {

    /**
     * The step exists but its dependencies are not yet satisfied.
     */
    public const PENDING = 'pending';

    /**
     * The step can be started.
     */
    public const READY = 'ready';

    /**
     * The step cannot proceed because of an explicit dependency or issue.
     */
    public const BLOCKED = 'blocked';

    /**
     * An administrator is currently handling the step.
     */
    public const IN_PROGRESS = 'in_progress';

    /**
     * The step has been completed.
     */
    public const COMPLETED = 'completed';

    /**
     * The step was deliberately skipped by an administrator.
     */
    public const SKIPPED = 'skipped';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::PENDING,
            self::READY,
            self::BLOCKED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::SKIPPED,
        ];
    }

    /**
     * @return string[]
     */
    public static function open(): array {
        return [
            self::PENDING,
            self::READY,
            self::BLOCKED,
            self::IN_PROGRESS,
        ];
    }

    /**
     * @return string[]
     */
    public static function terminal(): array {
        return [
            self::COMPLETED,
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

    public static function is_open(
        string $status
    ): bool {
        return in_array(
            $status,
            self::open(),
            true
        );
    }

    public static function is_terminal(
        string $status
    ): bool {
        return in_array(
            $status,
            self::terminal(),
            true
        );
    }
}