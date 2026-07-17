<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle statuses of a Customer Success plan.
 */
final class CustomerSuccessPlanStatus {

    /**
     * The plan was generated or manually prepared but has not been activated.
     */
    public const DRAFT = 'draft';

    /**
     * The plan is currently being executed.
     */
    public const ACTIVE = 'active';

    /**
     * The plan has temporarily been suspended.
     */
    public const PAUSED = 'paused';

    /**
     * All required steps have been completed.
     */
    public const COMPLETED = 'completed';

    /**
     * The plan was cancelled by an administrator.
     */
    public const CANCELLED = 'cancelled';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::PAUSED,
            self::COMPLETED,
            self::CANCELLED,
        ];
    }

    /**
     * @return string[]
     */
    public static function open(): array {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::PAUSED,
        ];
    }

    /**
     * @return string[]
     */
    public static function terminal(): array {
        return [
            self::COMPLETED,
            self::CANCELLED,
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